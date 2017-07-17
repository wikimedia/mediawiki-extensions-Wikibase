<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use LogicException;
use MWContentSerializationException;
use Status;
use User;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpValidationException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StringNormalizer;
use Wikibase\Summary;

/**
 * Base class for API modules modifying a single entity identified based on id xor a combination of site and page title.
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
	 * @var EntityTitleStoreLookup
	 */
	private $titleLookup;

	/**
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
	protected $errorReporter;

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
	 * @var string[]
	 */
	private $enabledEntityTypes;

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
		$this->enabledEntityTypes = $wikibaseRepo->getLocalEntityTypes();

		$this->entitySavingHelper->setEntityIdParam( 'id' );

		$this->setServices( new SiteLinkTargetProvider(
			$wikibaseRepo->getSiteLookup(),
			$settings->getSetting( 'specialSiteLinkGroups' )
		) );

		// TODO: use the EntitySavingHelper to load the entity, instead of an EntityRevisionLookup.
		$this->revisionLookup = $wikibaseRepo->getEntityRevisionLookup( 'uncached' );
		$this->permissionChecker = $wikibaseRepo->getEntityPermissionChecker();
		$this->titleLookup = $wikibaseRepo->getEntityTitleLookup();
		$this->siteLinkGroups = $settings->getSetting( 'siteLinkGroups' );
		$this->badgeItems = $settings->getSetting( 'badgeItems' );
	}

	public function setServices( SiteLinkTargetProvider $siteLinkTargetProvider ) {
		$this->siteLinkTargetProvider = $siteLinkTargetProvider;
	}

	/**
	 * @return EntityTitleStoreLookup
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
	 * @param EntityDocument &$entity
	 * @param ChangeOp $changeOp
	 * @param array $preparedParameters
	 *
	 * @return Summary|null a summary of the modification, or null to indicate failure.
	 */
	abstract protected function modifyEntity(
		EntityDocument &$entity,
		ChangeOp $changeOp,
		array $preparedParameters
	);

	/**
	 * Applies the given ChangeOp to the given Entity.
	 * Any ChangeOpException is converted into an ApiUsageException with the code 'modification-failed'.
	 *
	 * @param ChangeOp $changeOp
	 * @param EntityDocument $entity
	 * @param Summary|null $summary The summary object to update with information about the change.
	 */
	protected function applyChangeOp( ChangeOp $changeOp, EntityDocument $entity, Summary $summary = null ) {
		try {
			// NOTE: Always validate modification against the current revision, if it exists!
			//       Otherwise, we may miss e.g. a combination of language/label/description
			//       that was already taken.
			// TODO: conflict resolution should be re-engineered, see T126231
			// TODO: use the EntitySavingHelper to load the entity, instead of an EntityRevisionLookup.
			// TODO: consolidate with StatementModificationHelper::applyChangeOp
			// FIXME: this EntityRevisionLookup is uncached, we may be loading the Entity several times!
			$currentEntityRevision = $this->revisionLookup->getEntityRevision(
				$entity->getId(),
				0,
				EntityRevisionLookup::LATEST_FROM_REPLICA_WITH_FALLBACK
			);
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
	 * @param array $params
	 * @return array
	 */
	protected function prepareParameters( array $params ) {
		return $params;
	}

	protected function validateEntitySpecificParameters(
		array $preparedParameters,
		EntityDocument $entity,
		$baseRevId
	) {
	}

	/**
	 * Make sure the required parameters are provided and that they are valid.
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		$entityReferenceBySiteLinkGiven = isset( $params['site'] ) && isset( $params['title'] );
		$entityIdGiven = isset( $params['id'] );
		$shouldCreateNewWithSomeType = isset( $params['new'] );

		$createNew_AndOr_IdIsGiven = $entityIdGiven || $shouldCreateNewWithSomeType;

		$noReferenceIsGiven = !$createNew_AndOr_IdIsGiven && !$entityReferenceBySiteLinkGiven;
		$bothReferencesAreGiven = $createNew_AndOr_IdIsGiven && $entityReferenceBySiteLinkGiven;

		if ( $noReferenceIsGiven || $bothReferencesAreGiven ) {
			$this->errorReporter->dieWithError(
				'wikibase-api-illegal-id-or-site-page-selector',
				'param-illegal'
			);
		}
	}

	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$user = $this->getUser();

		$this->validateParameters( $params );

		// Try to find the entity or fail and create it, or die in the process
		$entity = $this->entitySavingHelper->loadEntity();
		$entityRevId = $this->entitySavingHelper->getBaseRevisionId();

		if ( $entity->getId() === null ) {
			throw new LogicException( 'The Entity should have an ID at this point!' );
		}

		$preparedParameters = $this->prepareParameters( $params );
		unset( $params );

		$this->validateEntitySpecificParameters( $preparedParameters, $entity, $entityRevId );

		$changeOp = $this->getChangeOp( $preparedParameters, $entity );

		$status = $this->checkPermissions( $entity, $user, $changeOp );

		if ( !$status->isOK() ) {
			// Was …->dieError( 'You do not have sufficient permissions', … ) before T150512.
			$this->errorReporter->dieStatus( $status, 'permissiondenied' );
		}

		$summary = $this->modifyEntity( $entity, $changeOp, $preparedParameters );

		if ( !$summary ) {
			//XXX: This could rather be used for "silent" failure, i.e. in cases where
			//     there was simply nothing to do.
			$this->errorReporter->dieError( 'Attempted modification of the item failed', 'failed-modify' );
		}

		try {
			$status = $this->entitySavingHelper->attemptSaveEntity(
				$entity,
				$summary
			);
		} catch ( MWContentSerializationException $ex ) {
			// This happens if the $entity created via modifyEntity() above (possibly cleared
			// before) is not sufficiently initialized and failed serialization.
			$this->errorReporter->dieError( $ex->getMessage(), 'failed-save' );
		}

		$this->addToOutput( $entity, $status, $entityRevId );
	}

	/**
	 * @param array $preparedParameters
	 * @return ChangeOp
	 */
	abstract protected function getChangeOp( array $preparedParameters, EntityDocument $entity );

	/**
	 * Check the rights for the user accessing the module.
	 *
	 * @param EntityDocument $entity the entity to check
	 * @param User $user User doing the action
	 * @param ChangeOp $changeOp
	 *
	 * @return Status the check's result
	 */
	private function checkPermissions( EntityDocument $entity, User $user, ChangeOp $changeOp ) {
		$status = Status::newGood();

		foreach ( $changeOp->getActions() as $perm ) {
			$permStatus = $this->permissionChecker->getPermissionStatusForEntity( $user, $perm, $entity );
			$status->merge( $permStatus );
		}

		return $status;
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
		return [
			'id' => [
				self::PARAM_TYPE => 'string',
			],
			'new' => [
				self::PARAM_TYPE => $this->enabledEntityTypes,
			],
		];
	}

	/**
	 * Get allowed params for the identification by a sitelink pair
	 * Lookup through the sitelink object is not used in every subclasses
	 *
	 * @return array[]
	 */
	private function getAllowedParamsForSiteLink() {
		$sites = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups );

		return [
			'site' => [
				self::PARAM_TYPE => $sites->getGlobalIdentifiers(),
			],
			'title' => [
				self::PARAM_TYPE => 'string',
			],
		];
	}

	/**
	 * Get allowed params for the entity in general
	 *
	 * @return array
	 */
	private function getAllowedParamsForEntity() {
		return [
			'baserevid' => [
				self::PARAM_TYPE => 'integer',
			],
			'summary' => [
				self::PARAM_TYPE => 'string',
			],
			'token' => null,
			'bot' => false,
		];
	}

}
