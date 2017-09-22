<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use Status;
use ApiUsageException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\EditEntity as EditEntityHandler;
use Wikibase\EditEntityFactory;
use Wikibase\EntityFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\FormatableSummary;
use Wikibase\SummaryFormatter;

/**
 * Helper class for api modules to save entities.
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Daniel Kinzler
 */
class EntitySavingHelper extends EntityLoadingHelper {

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var EditEntityFactory
	 */
	private $editEntityFactory;

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

	public function __construct(
		ApiBase $apiModule,
		EntityIdParser $idParser,
		EntityRevisionLookup $entityRevisionLookup,
		ApiErrorReporter $errorReporter,
		SummaryFormatter $summaryFormatter,
		EditEntityFactory $editEntityFactory
	) {
		parent::__construct( $apiModule, $idParser, $entityRevisionLookup, $errorReporter );

		$this->summaryFormatter = $summaryFormatter;
		$this->editEntityFactory = $editEntityFactory;

		$this->defaultRetrievalMode = EntityRevisionLookup::LATEST_FROM_MASTER;
	}

	/**
	 * @return int
	 */
	public function getBaseRevisionId() {
		return $this->baseRevisionId;
	}

	/**
	 * @return int
	 */
	public function getSaveFlags() {
		return $this->entitySavingFlags;
	}

	/**
	 * @return EntityFactory|null
	 */
	public function getEntityFactory() {
		return $this->entityFactory;
	}

	public function setEntityFactory( EntityFactory $entityFactory ) {
		$this->entityFactory = $entityFactory;
	}

	/**
	 * @return EntityStore|null
	 */
	public function getEntityStore() {
		return $this->entityStore;
	}

	public function setEntityStore( EntityStore $entityStore ) {
		$this->entityStore = $entityStore;
	}

	/**
	 * @param EntityId|null $entityId ID of the entity to load. If not given, the ID is taken
	 *        from the request parameters. If $entityId is given, the 'baserevid' parameter must
	 *        belong to it.
	 *
	 * @return EntityDocument
	 */
	public function loadEntity( EntityId $entityId = null ) {
		$params = $this->apiModule->extractRequestParams();

		if ( !$entityId ) {
			$entityId = $this->getEntityIdFromParams( $params );
		}

		// If a base revision is given, use if for consistency!
		$baseRev = isset( $params['baserevid'] )
			? (int)$params['baserevid']
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

		$new = isset( $params['new'] ) ? $params['new'] : null;
		if ( is_null( $entityRevision ) ) {
			if ( $baseRev > 0 ) {
				$this->errorReporter->dieError(
					'Could not find revision ' . $baseRev,
					'nosuchrevid'
				);
			}

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

			$entity = $this->createEntity( $new, $entityId );

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

	/**
	 * @return bool
	 */
	private function isEntityCreationSupported() {
		return $this->entityStore !== null && $this->entityFactory !== null;
	}

	/**
	 * Create an empty entity.
	 *
	 * @param string|null $entityType The type of entity to create. Optional if an ID is given.
	 * @param EntityId|null $customId Optionally assigns a specific ID instead of generating a new
	 *  one.
	 *
	 * @throws InvalidArgumentException when entity type and ID are given but do not match.
	 * @throws ApiUsageException
	 * @throws LogicException
	 * @return EntityDocument
	 */
	private function createEntity( $entityType, EntityId $customId = null ) {
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
		} else {
			try {
				// NOTE: We need to assign an ID early, for things like the ClaimIdGenerator.
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
	public function attemptSaveEntity( EntityDocument $entity, $summary, $flags = 0 ) {
		if ( !$this->apiModule->isWriteMode() ) {
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

		$params = $this->apiModule->extractRequestParams();
		$user = $this->apiModule->getContext()->getUser();

		if ( isset( $params['bot'] ) && $params['bot'] && $user->isAllowed( 'bot' ) ) {
			$flags |= EDIT_FORCE_BOT;
		}

		if ( !$this->baseRevisionId ) {
			$this->baseRevisionId = isset( $params['baserevid'] ) ? (int)$params['baserevid'] : 0;
		}

		$editEntityHandler = $this->editEntityFactory->newEditEntity(
			$user,
			$entity->getId(),
			$this->baseRevisionId,
			true
		);

		$token = $this->evaluateTokenParam( $params );

		$status = $editEntityHandler->attemptSave(
			$entity,
			$summary,
			$this->entitySavingFlags | $flags,
			$token
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
		if ( !$this->apiModule->needsToken() ) {
			// False disables the token check.
			return false;
		}

		// Null fails the token check.
		return isset( $params['token'] ) ? $params['token'] : null;
	}

	/**
	 * Signal errors and warnings from a save operation to the API call's output.
	 * This is much like handleStatus(), but specialized for Status objects returned by
	 * EditEntityHandler::attemptSave(). In particular, the 'errorFlags' and 'errorCode' fields
	 * from the status value are used to determine the error code to return to the caller.
	 *
	 * @note: this function may or may not return normally, depending on whether
	 *        the status is fatal or not.
	 *
	 * @see handleStatus().
	 *
	 * @param Status $status The status to report
	 */
	private function handleSaveStatus( Status $status ) {
		$value = $status->getValue();
		$errorCode = null;

		if ( is_array( $value ) && isset( $value['errorCode'] ) ) {
			$errorCode = $value['errorCode'];
		} else {
			$editError = 0;

			if ( is_array( $value ) && isset( $value['errorFlags'] ) ) {
				$editError = $value['errorFlags'];
			}

			if ( $editError & EditEntityHandler::TOKEN_ERROR ) {
				$errorCode = 'badtoken';
			} elseif ( $editError & EditEntityHandler::EDIT_CONFLICT_ERROR ) {
				$errorCode = 'editconflict';
			} elseif ( $editError & EditEntityHandler::ANY_ERROR ) {
				$errorCode = 'failed-save';
			}
		}

		//NOTE: will just add warnings or do nothing if there's no error
		$this->handleStatus( $status, $errorCode );
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
	private function handleStatus( Status $status, $errorCode ) {
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
