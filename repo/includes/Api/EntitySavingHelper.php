<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiUsageException;
use ArrayAccess;
use IContextSource;
use InvalidArgumentException;
use LogicException;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Revision\RevisionLookup;
use OutOfBoundsException;
use Status;
use TitleFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\EntityFactory;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\EditEntity\EditEntity;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SummaryFormatter;

/**
 * Helper class for api modules to save entities.
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Daniel Kinzler
 */
class EntitySavingHelper extends EntityLoadingHelper {

	public const ASSIGN_FRESH_ID = 'assignFreshId';
	public const NO_FRESH_ID = 'noFreshId';

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var MediawikiEditEntityFactory
	 */
	private $editEntityFactory;

	/**
	 * @var PermissionManager
	 */
	private $permissionManager;

	/**
	 * Flags to pass to EditEntity::attemptSave; This is set by loadEntity() to EDIT_NEW
	 * for new entities, and EDIT_UPDATE for existing entities.
	 *
	 * @see EditEntity::attemptSave
	 * @see WikiPage::doEditContent
	 *
	 * @var int
	 */
	private $entitySavingFlags = 0;

	/**
	 * Entity ID of the loaded entity.
	 *
	 * @var EntityId|null
	 */
	private $entityId = null;

	/**
	 * Base revision ID, for loading the entity revision for editing, and for avoiding
	 * race conditions.
	 *
	 * @var int
	 */
	private $baseRevisionId = 0;

	/**
	 * @var EntityFactory|null
	 */
	private $entityFactory = null;

	/**
	 * @var EntityStore|null
	 */
	private $entityStore = null;

	/**
	 * @var bool
	 */
	private $isApiModuleWriteMode = false;

	/**
	 * @var bool|string
	 */
	private $apiModuleNeedsToken = false;

	public function __construct(
		bool $isWriteMode,
		$needsToken,
		RevisionLookup $revisionLookup,
		TitleFactory $titleFactory,
		EntityIdParser $idParser,
		EntityRevisionLookup $entityRevisionLookup,
		EntityTitleStoreLookup $entityTitleStoreLookup,
		ApiErrorReporter $errorReporter,
		SummaryFormatter $summaryFormatter,
		MediawikiEditEntityFactory $editEntityFactory,
		PermissionManager $permissionManager
	) {
		parent::__construct(
			$revisionLookup,
			$titleFactory,
			$idParser,
			$entityRevisionLookup,
			$entityTitleStoreLookup,
			$errorReporter
		);

		$this->isApiModuleWriteMode = $isWriteMode;
		$this->apiModuleNeedsToken = $needsToken;
		$this->summaryFormatter = $summaryFormatter;
		$this->editEntityFactory = $editEntityFactory;
		$this->permissionManager = $permissionManager;

		$this->defaultRetrievalMode = LookupConstants::LATEST_FROM_MASTER;
	}

	public function getBaseRevisionId(): int {
		return $this->baseRevisionId;
	}

	public function getSaveFlags(): int {
		return $this->entitySavingFlags;
	}

	public function getEntityFactory(): ?EntityFactory {
		return $this->entityFactory;
	}

	public function setEntityFactory( EntityFactory $entityFactory ): void {
		$this->entityFactory = $entityFactory;
	}

	public function getEntityStore(): ?EntityStore {
		return $this->entityStore;
	}

	public function setEntityStore( EntityStore $entityStore ): void {
		$this->entityStore = $entityStore;
	}

	/**
	 * @param EntityId|null $entityId ID of the entity to load. If not given, the ID is taken
	 *        from the request parameters. If $entityId is given, the 'baserevid' parameter must
	 *        belong to it.
	 * @param string $assignFreshId Whether to allow assigning entity ids to new entities.
	 *        Either of the ASSIGN_FRESH_ID/NO_FRESH_ID constants.
	 *        NOTE: We usually need to assign an ID early, for things like the ClaimIdGenerator.
	 *
	 * @throws ApiUsageException
	 *
	 * @return EntityDocument
	 */
	public function loadEntity( array $requestParams, ?EntityId $entityId = null, $assignFreshId = self::ASSIGN_FRESH_ID ): EntityDocument {
		if ( !in_array( $assignFreshId, [ self::ASSIGN_FRESH_ID, self::NO_FRESH_ID ] ) ) {
			throw new InvalidArgumentException(
				'$assignFreshId must be either of the EntitySavingHelper::ASSIGN_FRESH_ID/NO_FRESH_ID constants.'
			);
		}

		if ( !$entityId ) {
			$entityId = $this->getEntityIdFromParams( $requestParams );
		}

		// If a base revision is given, use if for consistency!
		$baseRev = isset( $requestParams['baserevid'] )
			? (int)$requestParams['baserevid']
			: 0;

		if ( $entityId ) {
			$entityRevision = $this->loadEntityRevision( $entityId, $baseRev );
		} else {
			if ( $baseRev > 0 ) {
				$this->errorReporter->dieError(
					'Cannot load specific revision ' . $baseRev . ' if no entity is defined.',
					'param-illegal'
				);
			}

			$entityRevision = null;
		}

		$new = $requestParams['new'] ?? null;
		if ( $entityRevision === null ) {
			if ( !$this->isEntityCreationSupported() ) {
				if ( !$entityId ) {
					$this->errorReporter->dieError(
						'No entity ID provided, and entity cannot be created',
						'no-entity-id'
					);
				} else {
					$this->errorReporter->dieWithError( [ 'no-such-entity', $entityId ],
						'no-such-entity'
					);
				}
			}

			if ( !$entityId && !$new ) {
				$this->errorReporter->dieError(
					'No entity was identified, nor was creation requested',
					'no-entity-id'
				);
			}

			if ( $entityId && !$this->entityStore->canCreateWithCustomId( $entityId ) ) {
				$this->errorReporter->dieWithError( [ 'no-such-entity', $entityId ],
					'no-such-entity'
				);
			}

			$entity = $this->createEntity( $new, $entityId, $assignFreshId );

			$this->entitySavingFlags = EDIT_NEW;
			$this->baseRevisionId = 0;
		} else {
			$this->entitySavingFlags = EDIT_UPDATE;
			$this->baseRevisionId = $entityRevision->getRevisionId();
			$entity = $entityRevision->getEntity();
		}

		// remember the entity ID
		$this->entityId = $entity->getId();

		return $entity;
	}

	private function isEntityCreationSupported(): bool {
		return $this->entityStore !== null && $this->entityFactory !== null;
	}

	/**
	 * Create an empty entity.
	 *
	 * @param string|null $entityType The type of entity to create. Optional if an ID is given.
	 * @param EntityId|null $customId Optionally assigns a specific ID instead of generating a new
	 *  one.
	 * @param string $assignFreshId Either of the ASSIGN_FRESH_ID/NO_FRESH_ID constants
	 *               NOTE: We usually need to assign an ID early, for things like the ClaimIdGenerator.
	 *
	 * @throws InvalidArgumentException when entity type and ID are given but do not match.
	 * @throws ApiUsageException
	 * @throws LogicException
	 * @return EntityDocument
	 */
	private function createEntity( $entityType, EntityId $customId = null, $assignFreshId = self::ASSIGN_FRESH_ID ): EntityDocument {
		if ( $customId ) {
			$entityType = $customId->getEntityType();
		} elseif ( !$entityType ) {
			$this->errorReporter->dieError(
				"No entity type provided for creation!",
				'no-entity-type'
			);

			throw new LogicException( 'ApiErrorReporter::dieError did not throw an exception' );
		}

		try {
			$entity = $this->entityFactory->newEmpty( $entityType );
		} catch ( OutOfBoundsException $ex ) {
			$this->errorReporter->dieError(
				"No such entity type: '$entityType'",
				'no-such-entity-type'
			);

			throw new LogicException( 'ApiErrorReporter::dieError did not throw an exception' );
		}

		if ( $customId !== null ) {
			if ( !$this->entityStore->canCreateWithCustomId( $customId ) ) {
				$this->errorReporter->dieError(
					"Cannot create entity with ID: '$customId'",
					'bad-entity-id'
				);

				throw new LogicException( 'ApiErrorReporter::dieError did not throw an exception' );
			}

			$entity->setId( $customId );
		} elseif ( $assignFreshId === self::ASSIGN_FRESH_ID ) {
			try {
				$this->entityStore->assignFreshId( $entity );
			} catch ( StorageException $e ) {
				$this->errorReporter->dieError(
					'Cannot automatically assign ID: ' . $e->getMessage(),
					'no-automatic-entity-id'
				);

				throw new LogicException( 'ApiErrorReporter::dieError did not throw an exception' );
			}

		}

		return $entity;
	}

	/**
	 * Attempts to save the new entity content, while first checking for permissions,
	 * edit conflicts, etc. Saving is done via EditEntityHandler::attemptSave().
	 *
	 * This method automatically takes into account several parameters:
	 * * 'bot' for setting the bot flag
	 * * 'baserevid' for determining the edit's base revision for conflict resolution
	 * * 'token' for the edit token
	 * * 'tags' for change tags, assuming they were already permission checked by ApiBase
	 *   (i.e. PARAM_TYPE => 'tags')
	 *
	 * If an error occurs, it is automatically reported and execution of the API module
	 * is terminated using the ApiErrorReporter (via handleStatus()). If there were any
	 * warnings, they will automatically be included in the API call's output (again, via
	 * handleStatus()).
	 *
	 * @param EntityDocument $entity The entity to save
	 * @param string|FormatableSummary $summary The edit summary
	 * @param int $flags The edit flags (see WikiPage::doEditContent)
	 *
	 * @throws LogicException if not in write mode
	 * @return Status the status of the save operation, as returned by EditEntityHandler::attemptSave()
	 * @see  EditEntityHandler::attemptSave()
	 */
	public function attemptSaveEntity(
		EntityDocument $entity,
		$summary,
		array $requestParams,
		IContextSource $context,
		int $flags = 0
	): Status {
		if ( !$this->isApiModuleWriteMode ) {
			// sanity/safety check
			throw new LogicException(
				'attemptSaveEntity() cannot be used by API modules that do not return true from isWriteMode()!'
			);
		}

		if ( $this->entityId !== null && !$entity->getId()->equals( $this->entityId ) ) {
			// sanity/safety check
			throw new LogicException(
				'attemptSaveEntity() was expecting to be called on '
					. $this->entityId->getSerialization() . '!'
			);
		}

		if ( $summary instanceof FormatableSummary ) {
			$summary = $this->summaryFormatter->formatSummary( $summary );
		}

		$user = $context->getUser();

		if ( isset( $requestParams['bot'] ) && $requestParams['bot'] &&
			$this->permissionManager->userHasRight( $user, 'bot' )
		) {
			$flags |= EDIT_FORCE_BOT;
		}

		if ( !$this->baseRevisionId ) {
			$this->baseRevisionId = isset( $requestParams['baserevid'] ) ? (int)$requestParams['baserevid'] : 0;
		}

		$tags = $requestParams['tags'] ?? [];

		$editEntityHandler = $this->editEntityFactory->newEditEntity(
			$context,
			$entity->getId(),
			$this->baseRevisionId,
			true
		);

		$token = $this->evaluateTokenParam( $requestParams );

		$status = $editEntityHandler->attemptSave(
			$entity,
			$summary,
			$this->entitySavingFlags | $flags,
			$token,
			null,
			$tags
		);

		$this->handleSaveStatus( $status );
		return $status;
	}

	/**
	 * @param array $params
	 *
	 * @return string|bool|null Token string, or false if not needed, or null if not set.
	 */
	private function evaluateTokenParam( array $params ) {
		if ( !$this->apiModuleNeedsToken ) {
			// False disables the token check.
			return false;
		}

		// Null fails the token check.
		return $params['token'] ?? null;
	}

	/**
	 * Signal errors and warnings from a save operation to the API call's output.
	 * This is much like handleStatus(), but specialized for Status objects returned by
	 * EditEntityHandler::attemptSave(). In particular, the 'errorFlags' and 'errorCode' fields
	 * from the status value are used to determine the error code to return to the caller.
	 *
	 * @note this function may or may not return normally, depending on whether
	 *        the status is fatal or not.
	 *
	 * @see handleStatus().
	 *
	 * @param Status $status The status to report
	 */
	private function handleSaveStatus( Status $status ): void {
		$value = $status->getValue();
		$errorCode = null;

		if ( $this->isArrayLike( $value ) && isset( $value['errorCode'] ) ) {
			$errorCode = $value['errorCode'];
		} else {
			$editError = 0;

			if ( $this->isArrayLike( $value ) && isset( $value['errorFlags'] ) ) {
				$editError = $value['errorFlags'];
			}

			if ( $editError & EditEntity::TOKEN_ERROR ) {
				$errorCode = 'badtoken';
			} elseif ( $editError & EditEntity::EDIT_CONFLICT_ERROR ) {
				$errorCode = 'editconflict';
			} elseif ( $editError & EditEntity::ANY_ERROR ) {
				$errorCode = 'failed-save';
			}
		}

		//NOTE: will just add warnings or do nothing if there's no error
		$this->handleStatus( $status, $errorCode );
	}

	/**
	 * Checks whether accessing array keys is safe, with e.g. @see DeprecatablePropertyArray
	 */
	private function isArrayLike( $value ): bool {
		return is_array( $value ) || $value instanceof ArrayAccess;
	}

	/**
	 * Include messages from a Status object in the API call's output.
	 *
	 * An ApiErrorHandler is used to report the status, if necessary.
	 * If $status->isOK() is false, this method will terminate with an ApiUsageException.
	 *
	 * @param Status $status The status to report
	 * @param string  $errorCode The API error code to use in case $status->isOK() returns false
	 *
	 * @throws ApiUsageException If $status->isOK() returns false.
	 */
	private function handleStatus( Status $status, $errorCode ): void {
		if ( $status->isGood() ) {
			return;
		} elseif ( $status->isOK() ) {
			$this->errorReporter->reportStatusWarnings( $status );
		} else {
			$this->errorReporter->reportStatusWarnings( $status );
			$this->errorReporter->dieStatus( $status, $errorCode );
		}
	}

}
