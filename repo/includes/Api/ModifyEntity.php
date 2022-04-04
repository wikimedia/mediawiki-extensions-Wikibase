<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use ApiUsageException;
use LogicException;
use MWContentSerializationException;
use Status;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpResult;
use Wikibase\Repo\ChangeOp\ChangeOpValidationException;
use Wikibase\Repo\SiteLinkGlobalIdentifiersProvider;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Base class for API modules modifying a single entity identified based on id xor a combination of site and page title.
 *
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 * @author Michał Łazowik
 */
abstract class ModifyEntity extends ApiBase {

	use FederatedPropertyApiValidatorTrait;

	/**
	 * @var StringNormalizer
	 */
	protected $stringNormalizer;

	/**
	 * @var SiteLinkGlobalIdentifiersProvider
	 */
	protected $siteLinkGlobalIdentifiersProvider;

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
	protected $enabledEntityTypes;

	/**
	 * @var bool
	 */
	private $isFreshIdAssigned;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param bool $federatedPropertiesEnabled
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, string $moduleName, bool $federatedPropertiesEnabled, string $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$apiHelperFactory = WikibaseRepo::getApiHelperFactory();
		$settings = WikibaseRepo::getSettings();

		//TODO: provide a mechanism to override the services
		$this->errorReporter = $apiHelperFactory->getErrorReporter( $this );
		$this->resultBuilder = $apiHelperFactory->getResultBuilder( $this );
		$this->entitySavingHelper = $apiHelperFactory->getEntitySavingHelper( $this );
		$this->stringNormalizer = WikibaseRepo::getStringNormalizer();
		$this->enabledEntityTypes = WikibaseRepo::getLocalEntityTypes();

		$this->entitySavingHelper->setEntityIdParam( 'id' );

		$this->setServices( WikibaseRepo::getSiteLinkGlobalIdentifiersProvider() );

		// TODO: use the EntitySavingHelper to load the entity, instead of an EntityRevisionLookup.
		$this->revisionLookup = WikibaseRepo::getStore()->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED );
		$this->permissionChecker = WikibaseRepo::getEntityPermissionChecker();
		$this->titleLookup = WikibaseRepo::getEntityTitleStoreLookup();
		$this->siteLinkGroups = $settings->getSetting( 'siteLinkGroups' );
		$this->badgeItems = $settings->getSetting( 'badgeItems' );

		$this->federatedPropertiesEnabled = $federatedPropertiesEnabled;
		$this->isFreshIdAssigned = false;
	}

	public function setServices( SiteLinkGlobalIdentifiersProvider $siteLinkGlobalIdentifiersProvider ): void {
		$this->siteLinkGlobalIdentifiersProvider = $siteLinkGlobalIdentifiersProvider;
	}

	protected function getTitleLookup(): EntityTitleStoreLookup {
		return $this->titleLookup;
	}

	protected function getResultBuilder(): ResultBuilder {
		return $this->resultBuilder;
	}

	/**
	 * Create a new Summary instance suitable for representing the action performed by this module.
	 *
	 * @param array $params
	 *
	 * @return Summary
	 */
	protected function createSummary( array $params ): Summary {
		$summary = new Summary( $this->getModuleName() );
		$summary->setUserSummary( $params['summary'] );
		return $summary;
	}

	/**
	 * Actually modify the entity.
	 *
	 * @param EntityDocument $entity
	 * @param ChangeOp $changeOp
	 * @param array $preparedParameters
	 *
	 * @return Summary|null a summary of the modification, or null to indicate failure.
	 */
	abstract protected function modifyEntity(
		EntityDocument $entity,
		ChangeOp $changeOp,
		array $preparedParameters
	): ?Summary;

	/**
	 * Applies the given ChangeOp to the given Entity.
	 * Any ChangeOpException is converted into an ApiUsageException with the code 'modification-failed'.
	 *
	 * @param ChangeOp $changeOp
	 * @param EntityDocument $entity
	 * @param Summary|null $summary The summary object to update with information about the change.
	 *
	 * @return ChangeOpResult
	 */
	protected function applyChangeOp( ChangeOp $changeOp, EntityDocument $entity, Summary $summary = null ): ChangeOpResult {
		try {
			// NOTE: Always validate modification against the current revision, if it exists!
			//       Otherwise, we may miss e.g. a combination of language/label/description
			//       that was already taken.
			// TODO: conflict resolution should be re-engineered, see T126231
			// TODO: use the EntitySavingHelper to load the entity, instead of an EntityRevisionLookup.
			// TODO: consolidate with StatementModificationHelper::applyChangeOp
			// FIXME: this EntityRevisionLookup is uncached, we may be loading the Entity several times!
			try {
				$currentEntityRevision = $this->revisionLookup->getEntityRevision(
					$entity->getId(),
					0,
					LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK
				);
			} catch ( RevisionedUnresolvedRedirectException $ex ) {
				$this->errorReporter->dieException( $ex, 'unresolved-redirect' );
			}

			if ( $currentEntityRevision ) {
				$currentEntityResult = $changeOp->validate( $currentEntityRevision->getEntity() );
				if ( !$currentEntityResult->isValid() ) {
					throw new ChangeOpValidationException( $currentEntityResult );
				}
			}

			// Also validate the change op against the entity it would be applied on, as apply might
			// explode on cases validate would have caught.
			// Case for that seem to be a "clear" flag of wbeditentity which results in $entity being
			// quite a different entity from $currentEntity, and validation results might differ significantly.
			$result = $changeOp->validate( $entity );

			if ( !$result->isValid() ) {
				throw new ChangeOpValidationException( $result );
			}

			$changeOpResult = $changeOp->apply( $entity, $summary );

			// Also validate change op result as it may contain further validation
			// that is not covered by change op validators
			$changeOpResultValidationResult = $changeOpResult->validate();

			if ( !$changeOpResultValidationResult->isValid() ) {
				throw new ChangeOpValidationException( $changeOpResultValidationResult );
			}

			return $changeOpResult;

		} catch ( ChangeOpException $ex ) {
			$this->errorReporter->dieException( $ex, 'modification-failed' );
		}
	}

	/**
	 * @param array $params
	 * @return array
	 */
	protected function prepareParameters( array $params ): array {
		return $params;
	}

	protected function validateEntitySpecificParameters(
		array $preparedParameters,
		EntityDocument $entity,
		int $baseRevId
	): void {
	}

	/**
	 * Make sure the required parameters are provided and that they are valid.
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ): void {
		$entityReferenceBySiteLinkGiven = isset( $params['site'] ) && isset( $params['title'] );
		$entityReferenceBySiteLinkPartial = ( isset( $params['site'] ) xor isset( $params['title'] ) );
		$entityIdGiven = isset( $params['id'] );
		$shouldCreateNewWithSomeType = isset( $params['new'] );

		$noReferenceIsGiven = !$entityIdGiven && !$shouldCreateNewWithSomeType && !$entityReferenceBySiteLinkGiven;
		$bothReferencesAreGiven = $entityIdGiven && $entityReferenceBySiteLinkGiven;

		if ( $entityReferenceBySiteLinkPartial ) {
			$this->errorReporter->dieWithError(
				'wikibase-api-illegal-id-or-site-page-selector',
				'param-missing'
			);
		}

		if ( $noReferenceIsGiven || $bothReferencesAreGiven ) {
			$this->errorReporter->dieWithError(
				'wikibase-api-illegal-id-or-site-page-selector',
				'param-illegal'
			);
		}

		if ( $shouldCreateNewWithSomeType && ( $entityIdGiven || $entityReferenceBySiteLinkGiven ) ) {
			$this->errorReporter->dieWithError(
				'Either provide the item "id" or pairs of "site" and "title" or a "new" type for an entity',
				'param-illegal'
			);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function execute(): void {
		$params = $this->extractRequestParams();
		$user = $this->getUser();

		$this->validateParameters( $params );
		$entityId = $this->entitySavingHelper->getEntityIdFromParams( $params );
		$this->validateAlteringEntityById( $entityId );

		// Try to find the entity or fail and create it, or die in the process
		$entity = $this->loadEntityFromSavingHelper( $entityId );
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
				$summary,
				$this->extractRequestParams(),
				$this->getContext()
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
	 * @param EntityDocument $entity
	 *
	 * @return ChangeOp
	 */
	abstract protected function getChangeOp( array $preparedParameters, EntityDocument $entity ): ChangeOp;

	/**
	 * Try to find the entity or fail and create it, or die in the process.
	 *
	 * @param EntityId|null $entityId
	 *
	 * @return EntityDocument
	 * @throws ApiUsageException
	 */
	private function loadEntityFromSavingHelper( ?EntityId $entityId ): EntityDocument {
		$params = $this->extractRequestParams();
		$entity = $this->entitySavingHelper->loadEntity( $params, $entityId, EntitySavingHelper::NO_FRESH_ID );

		if ( $entity->getId() === null ) {
			// Make sure the user is allowed to create an entity before attempting to assign an id
			$permStatus = $this->permissionChecker->getPermissionStatusForEntity(
				$this->getUser(),
				EntityPermissionChecker::ACTION_EDIT,
				$entity
			);
			if ( !$permStatus->isOK() ) {
				$this->errorReporter->dieStatus( $permStatus, 'permissiondenied' );
			}

			$entity = $this->entitySavingHelper->loadEntity( $params, $entityId, EntitySavingHelper::ASSIGN_FRESH_ID );
			$this->isFreshIdAssigned = true;
		}

		return $entity;
	}

	/**
	 * Return whether a fresh id is assigned or not.
	 *
	 * @return bool false if fresh id is not assigned, true otherwise
	 */
	public function isFreshIdAssigned(): bool {
		return $this->isFreshIdAssigned;
	}

	/**
	 * Check the rights for the user accessing the module.
	 *
	 * @param EntityDocument $entity the entity to check
	 * @param User $user User doing the action
	 * @param ChangeOp $changeOp
	 *
	 * @return Status the check's result
	 */
	private function checkPermissions( EntityDocument $entity, User $user, ChangeOp $changeOp ): Status {
		$status = Status::newGood();

		foreach ( $changeOp->getActions() as $perm ) {
			$permStatus = $this->permissionChecker->getPermissionStatusForEntity( $user, $perm, $entity );
			$status->merge( $permStatus );
		}

		return $status;
	}

	private function addToOutput( EntityDocument $entity, Status $status, int $oldRevId ): void {
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
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
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
	private function getAllowedParamsForId(): array {
		return [
			'id' => [
				ParamValidator::PARAM_TYPE => 'string',
			],
			'new' => [
				ParamValidator::PARAM_TYPE => $this->enabledEntityTypes,
			],
		];
	}

	/**
	 * Get allowed params for the identification by a sitelink pair
	 * Lookup through the sitelink object is not used in every subclasses
	 *
	 * @return array[]
	 */
	private function getAllowedParamsForSiteLink(): array {
		$siteIds = $this->siteLinkGlobalIdentifiersProvider->getList( $this->siteLinkGroups );

		return [
			'site' => [
				ParamValidator::PARAM_TYPE => $siteIds,
			],
			'title' => [
				ParamValidator::PARAM_TYPE => 'string',
			],
		];
	}

	/**
	 * Get allowed params for the entity in general
	 *
	 * @return array
	 */
	private function getAllowedParamsForEntity(): array {
		return [
			'baserevid' => [
				ParamValidator::PARAM_TYPE => 'integer',
			],
			'summary' => [
				ParamValidator::PARAM_TYPE => 'string',
			],
			'tags' => [
				ParamValidator::PARAM_TYPE => 'tags',
				ParamValidator::PARAM_ISMULTI => true,
			],
			'token' => null,
			'bot' => false,
		];
	}

}
