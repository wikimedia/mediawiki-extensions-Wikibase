<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use InvalidArgumentException;
use LogicException;
use Status;
use UsageException;
use User;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOpValidationException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\EntityFactory;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StringNormalizer;
use Wikibase\Summary;

/**
 * Base class for API modules modifying a single entity identified based on id xor a combination of site and page title.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 * @author Michał Łazowik
 */
abstract class ModifyEntity extends ApiBase {

	/**
	 * @var StringNormalizer
	 */
	protected $stringNormalizer;

	/**
	 * @var SiteLinkTargetProvider
	 */
	protected $siteLinkTargetProvider;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var EntityStore
	 */
	protected $entityStore;

	/**
	 * @since 0.5
	 *
	 * @var string[]
	 */
	protected $siteLinkGroups;

	/**
	 * @var string[]
	 */
	protected $badgeItems;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var EntityPermissionChecker
	 */
	private $permissionChecker;

	/**
	 * @var EntityRevisionLookup
	 */
	private $revisionLookup;

	/**
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * @var EntitySavingHelper
	 */
	private $entitySavingHelper;

	/**
	 * @var EntityIdParser
	 */
	protected $idParser;

	/**
	 * @var EntityFactory
	 */
	protected $entityFactory;

	/**
	 * @var string[]
	 */
	private $enabledEntityTypes;

	/**
	 * Flags to pass to EditEntity::attemptSave; use with the EDIT_XXX constants.
	 *
	 * @see EditEntity::attemptSave
	 * @see WikiPage::doEditContent
	 *
	 * @var int
	 */
	protected $flags;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $this->getContext() );
		$settings = $wikibaseRepo->getSettings();

		//TODO: provide a mechanism to override the services
		$this->errorReporter = $apiHelperFactory->getErrorReporter( $this );
		$this->resultBuilder = $apiHelperFactory->getResultBuilder( $this );
		$this->entitySavingHelper = $apiHelperFactory->getEntitySavingHelper( $this );
		$this->stringNormalizer = $wikibaseRepo->getStringNormalizer();
		$this->idParser = $wikibaseRepo->getEntityIdParser();
		$this->entityFactory = $wikibaseRepo->getEntityFactory();
		$this->enabledEntityTypes = $wikibaseRepo->getEnabledEntityTypes();

		$this->setServices( new SiteLinkTargetProvider(
			$wikibaseRepo->getSiteStore(),
			$settings->getSetting( 'specialSiteLinkGroups' )
		) );

		$this->revisionLookup = $wikibaseRepo->getEntityRevisionLookup( 'uncached' );
		$this->permissionChecker = $wikibaseRepo->getEntityPermissionChecker();
		$this->entityStore = $wikibaseRepo->getEntityStore();
		$this->titleLookup = $wikibaseRepo->getEntityTitleLookup();
		$this->siteLinkGroups = $settings->getSetting( 'siteLinkGroups' );
		$this->siteLinkLookup = $wikibaseRepo->getStore()->newSiteLinkStore();
		$this->badgeItems = $settings->getSetting( 'badgeItems' );
	}

	public function setServices( SiteLinkTargetProvider $siteLinkTargetProvider ) {
		$this->siteLinkTargetProvider = $siteLinkTargetProvider;
	}

	/**
	 * @see EntitySavingHelper::attemptSaveEntity
	 */
	private function attemptSaveEntity( EntityDocument $entity, $summary, $flags = 0 ) {
		// TODO: we should pass the revision ID of the current revision loaded by
		// applyChangeOp() to the storage layer, to avoid race conditions for
		// concurrent edits.
		// TODO: this should be re-engineered, see T126231
		return $this->entitySavingHelper->attemptSaveEntity( $entity, $summary, $flags );
	}

	/**
	 * @return EntityTitleLookup
	 */
	protected function getTitleLookup() {
		return $this->titleLookup;
	}

	/**
	 * @return ResultBuilder
	 */
	protected function getResultBuilder() {
		return $this->resultBuilder;
	}

	/**
	 * Get an EntityRevision using the id, site and title params as well as the
	 * baserevid passed to the api.
	 *
	 * @param array $params
	 *
	 * @return EntityRevision|null Found existing entity
	 */
	protected function getEntityRevisionFromApiParams( array $params ) {
		$entityRevision = null;
		$entityId = $this->getEntityIdFromParams( $params );

		// Things that use this method assume null means we want a new entity
		if ( $entityId !== null ) {
			$baseRevisionId = isset( $params['baserevid'] ) ? (int)$params['baserevid'] : 0;

			if ( $baseRevisionId === 0 ) {
				$baseRevisionId = EntityRevisionLookup::LATEST_FROM_MASTER;
			}

			try {
				$entityRevision = $this->revisionLookup->getEntityRevision( $entityId, $baseRevisionId );
			} catch ( EntityLookupException $ex ) {
				$this->errorReporter->dieException( $ex, 'no-such-entity' );
			} catch ( StorageException $ex ) {
				// @fixme EntityRevisionLookup still throws BadRevisionException, which
				// is a subclass of StorageException, so we still have some inconsistency
				// and need to check both.
				$this->errorReporter->dieException( $ex, 'no-such-entity' );
			}
		}

		return $entityRevision;
	}

	/**
	 * @param string[] $params
	 *
	 * @return EntityId|null
	 */
	private function getEntityIdFromParams( array $params ) {
		if ( isset( $params['id'] ) ) {
			return $this->getEntityIdFromString( $params['id'] );
		} elseif ( isset( $params['site'] ) && isset( $params['title'] ) ) {
			return $this->getEntityIdFromSiteTitleCombination(
				$params['site'],
				$params['title']
			);
		}

		return null;
	}

	/**
	 * Returns an EntityId object based on the given $id,
	 * or throws a usage exception if the ID is invalid.
	 *
	 * @param string $id
	 *
	 * @throws UsageException
	 * @return EntityId
	 */
	private function getEntityIdFromString( $id ) {
		try {
			return $this->idParser->parse( $id );
		} catch ( EntityIdParsingException $ex ) {
			$this->errorReporter->dieException( $ex, 'no-such-entity-id' );
		}

		return null;
	}

	/**
	 * @param string $site
	 * @param string $title
	 *
	 * @throws UsageException If no such entity is found.
	 * @return EntityId The ID of the entity connected to $title on $site.
	 */
	private function getEntityIdFromSiteTitleCombination( $site, $title ) {
		// FIXME: Normalization missing, see T47282.
		$itemId = $this->siteLinkLookup->getItemIdForLink( $site, $title );

		if ( $itemId === null ) {
			$this->errorReporter->dieError( 'No entity found matching site link ' . $site . ':' . $title,
				'no-such-entity-link' );
		}

		return $itemId;
	}

	/**
	 * Validates badges from params and turns them into an array of ItemIds.
	 *
	 * @param string[] $badgesParams
	 *
	 * @return ItemId[]
	 */
	protected function parseSiteLinkBadges( array $badgesParams ) {
		$badges = array();

		foreach ( $badgesParams as $badgeSerialization ) {
			try {
				$badgeId = new ItemId( $badgeSerialization );
			} catch ( InvalidArgumentException $ex ) {
				$this->errorReporter->dieError( 'Badges: could not parse "' . $badgeSerialization
					. '", the id is invalid', 'invalid-entity-id' );
				continue;
			}

			if ( !array_key_exists( $badgeId->getSerialization(), $this->badgeItems ) ) {
				$this->errorReporter->dieError( 'Badges: item "' . $badgeSerialization . '" is not a badge',
					'not-badge' );
			}

			$itemTitle = $this->getTitleLookup()->getTitleForId( $badgeId );

			if ( is_null( $itemTitle ) || !$itemTitle->exists() ) {
				$this->errorReporter->dieError(
					'Badges: no item found matching id "' . $badgeSerialization . '"',
					'no-such-entity'
				);
			}

			$badges[] = $badgeId;
		}

		return $badges;
	}

	/**
	 * Create an empty entity.
	 *
	 * @since 0.1
	 *
	 * @param string|null $entityType The type of entity to be created (ignored if $id is given)
	 * @param EntityId|null $id The ID of the entity to be created (optional if $entityType is
	 *        given)
	 *
	 * @throws UsageException
	 * @throws LogicException
	 * @return EntityDocument Newly created entity
	 */
	protected function createEntity( $entityType, EntityId $id = null ) {
		if ( $id ) {
			$entityType = $id->getEntityType();
		} elseif ( !$entityType ) {
			$this->errorReporter->dieError( "No entity type provided for creation!", 'no-entity-type' );
			throw new LogicException( 'ApiErrorReporter::dieError did not throw an exception' );
		}

		try {
			$entity = $this->entityFactory->newEmpty( $entityType );
		} catch ( InvalidArgumentException $ex ) {
			$this->errorReporter->dieError( "No such entity type: '$entityType'", 'no-such-entity-type' );
			throw new LogicException( 'ApiErrorReporter::dieError did not throw an exception' );
		}

		if ( $id !== null ) {
			if ( !$this->entityStore->canCreateWithCustomId( $id ) ) {
				$this->errorReporter->dieError( "Cannot create entity with ID: '$id'", 'bad-entity-id' );
				throw new LogicException( 'ApiErrorReporter::dieError did not throw an exception' );
			}

			$entity->setId( $id );
		} else {
			// NOTE: We need to assign an ID early, for things like the ClaimIdGenerator.
			$this->entityStore->assignFreshId( $entity );
		}

		return $entity;
	}

	/**
	 * Create a new Summary instance suitable for representing the action performed by this module.
	 *
	 * @param array $params
	 *
	 * @return Summary
	 */
	protected function createSummary( array $params ) {
		$summary = new Summary( $this->getModuleName() );
		$summary->setUserSummary( $params['summary'] );
		return $summary;
	}

	/**
	 * Actually modify the entity.
	 *
	 * @since 0.1
	 *
	 * @param EntityDocument &$entity
	 * @param array $params
	 * @param int $baseRevId
	 *
	 * @return Summary|null a summary of the modification, or null to indicate failure.
	 */
	abstract protected function modifyEntity( EntityDocument &$entity, array $params, $baseRevId );

	/**
	 * Applies the given ChangeOp to the given Entity.
	 * Any ChangeOpException is converted into a UsageException with the code 'modification-failed'.
	 *
	 * @since 0.5
	 *
	 * @param ChangeOp $changeOp
	 * @param EntityDocument $entity
	 * @param Summary|null $summary The summary object to update with information about the change.
	 */
	protected function applyChangeOp( ChangeOp $changeOp, EntityDocument $entity, Summary $summary = null ) {
		try {
			// NOTE: always validate modification against the current revision, if it exists!
			// TODO: this should be re-engineered, see T126231
			// TODO: attemptSaveEntity() should somehow get the ID of the current revision.
			$currentEntityRevision = $this->revisionLookup->getEntityRevision( $entity->getId() );
			$currentEntity = $currentEntityRevision ? $currentEntityRevision->getEntity() : $entity;
			$result = $changeOp->validate( $currentEntity );

			if ( !$result->isValid() ) {
				throw new ChangeOpValidationException( $result );
			}

			$changeOp->apply( $entity, $summary );
		} catch ( ChangeOpException $ex ) {
			$this->errorReporter->dieException( $ex, 'modification-failed' );
		}
	}

	/**
	 * Make sure the required parameters are provided and that they are valid.
	 *
	 * @since 0.1
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		if ( ( isset( $params['id'] ) || isset( $params['new'] ) )
			=== ( isset( $params['site'] ) && isset( $params['title'] ) )
		) {
			$this->errorReporter->dieError(
				'Either provide the item "id" or pairs of "site" and "title" for a corresponding page',
				'param-illegal'
			);
		}
	}

	/**
	 * @see ApiBase::execute()
	 *
	 * @since 0.1
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$user = $this->getUser();
		$this->flags = 0;

		$this->validateParameters( $params );

		// Try to find the entity or fail and create it, or die in the process
		$entityRev = $this->getEntityRevisionFromApiParams( $params );
		if ( is_null( $entityRev ) ) {
			$entityId = $this->getEntityIdFromParams( $params );

			if ( !$params['new'] ) {
				if ( !$entityId ) {
					$this->errorReporter->dieError(
						'No entity was identified, nor was creation requested',
						'param-illegal'
					);
				} elseif ( !$this->entityStore->canCreateWithCustomId( $entityId ) ) {
					$this->errorReporter->dieError(
						'Could not find entity ' . $entityId,
						'no-such-entity'
					);
				}
			}

			$entity = $this->createEntity( $params['new'], $entityId );

			$this->flags |= EDIT_NEW;
			$entityRevId = 0;
		} else {
			$entity = $entityRev->getEntity();
			$entityRevId = $entityRev->getRevisionId();
		}

		if ( $entity->getId() === null ) {
			throw new LogicException( 'The Entity should have an ID at this point!' );
		}

		// At this point only change/edit rights should be checked
		$status = $this->checkPermissions( $entity, $user );

		if ( !$status->isOK() ) {
			$this->errorReporter->dieError( 'You do not have sufficient permissions', 'permissiondenied' );
		}

		$summary = $this->modifyEntity( $entity, $params, $entityRevId );

		if ( !$summary ) {
			//XXX: This could rather be used for "silent" failure, i.e. in cases where
			//     there was simply nothing to do.
			$this->errorReporter->dieError( 'Attempted modification of the item failed', 'failed-modify' );
		}

		$this->addFlags( $entity->getId() === null );

		//NOTE: EDIT_NEW will not be set automatically. If the entity doesn't exist, and EDIT_NEW was
		//      not added to $this->flags explicitly, the save will fail.
		$status = $this->attemptSaveEntity(
			$entity,
			$summary,
			$this->flags
		);

		$this->addToOutput( $entity, $status, $entityRevId );
	}

	/**
	 * Check the rights for the user accessing the module.
	 *
	 * @param EntityDocument $entity the entity to check
	 * @param User $user User doing the action
	 *
	 * @return Status the check's result
	 */
	private function checkPermissions( EntityDocument $entity, User $user ) {
		$permissions = $this->getRequiredPermissions( $entity );
		$status = Status::newGood();

		foreach ( array_unique( $permissions ) as $perm ) {
			$permStatus = $this->permissionChecker->getPermissionStatusForEntity( $user, $perm, $entity );
			$status->merge( $permStatus );
		}

		return $status;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return string[]
	 */
	protected function getRequiredPermissions( EntityDocument $entity ) {
		return $this->isWriteMode() ? array( 'read', 'edit' ) : array( 'read' );
	}

	/**
	 * @param bool $entityIsNew
	 */
	private function addFlags( $entityIsNew ) {
		// if the entity is not up for creation, set the EDIT_UPDATE flags
		if ( !$entityIsNew && ( $this->flags & EDIT_NEW ) === 0 ) {
			$this->flags |= EDIT_UPDATE;
		}

		$params = $this->extractRequestParams();
		$this->flags |= ( $this->getUser()->isAllowed( 'bot' ) && $params['bot'] ) ? EDIT_FORCE_BOT : 0;
	}

	private function addToOutput( EntityDocument $entity, Status $status, $oldRevId = null ) {
		$this->getResultBuilder()->addBasicEntityInformation( $entity->getId(), 'entity' );
		$this->getResultBuilder()->addRevisionIdFromStatusToResult( $status, 'entity', $oldRevId );

		$params = $this->extractRequestParams();

		if ( isset( $params['site'] ) && isset( $params['title'] ) ) {
			$normTitle = $this->stringNormalizer->trimToNFC( $params['title'] );
			if ( $normTitle !== $params['title'] ) {
				$this->getResultBuilder()->addNormalizedTitle( $params['title'], $normTitle, 'normalized' );
			}
		}

		$this->getResultBuilder()->markSuccess( 1 );
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			parent::getAllowedParams(),
			$this->getAllowedParamsForId(),
			$this->getAllowedParamsForSiteLink(),
			$this->getAllowedParamsForEntity()
		);
	}

	/**
	 * Get allowed params for the identification of the entity
	 * Lookup through an id is common for all entities
	 *
	 * @return array[]
	 */
	private function getAllowedParamsForId() {
		return array(
			'id' => array(
				self::PARAM_TYPE => 'string',
			),
			'new' => array(
				self::PARAM_TYPE => $this->enabledEntityTypes,
			),
		);
	}

	/**
	 * Get allowed params for the identification by a sitelink pair
	 * Lookup through the sitelink object is not used in every subclasses
	 *
	 * @return array[]
	 */
	private function getAllowedParamsForSiteLink() {
		$sites = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups );

		return array(
			'site' => array(
				self::PARAM_TYPE => $sites->getGlobalIdentifiers(),
			),
			'title' => array(
				self::PARAM_TYPE => 'string',
			),
		);
	}

	/**
	 * Get allowed params for the entity in general
	 *
	 * @return array
	 */
	private function getAllowedParamsForEntity() {
		return array(
			'baserevid' => array(
				self::PARAM_TYPE => 'integer',
			),
			'summary' => array(
				self::PARAM_TYPE => 'string',
			),
			'token' => null,
			'bot' => false,
		);
	}

}
