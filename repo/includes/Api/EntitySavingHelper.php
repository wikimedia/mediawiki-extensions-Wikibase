<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use InvalidArgumentException;
use LogicException;
use Status;
use UsageException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EditEntity as EditEntityHandler;
use Wikibase\EditEntityFactory;
use Wikibase\EntityFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Helper class for api modules to save entities.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class EntitySavingHelper extends EntityLoadingHelper {

	/**
	 * @var ApiBase
	 */
	private $apiBase;

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var EditEntityFactory
	 */
	private $editEntityFactory;

	/**
	 * Flags to pass to EditEntity::attemptSave; use with the EDIT_XXX constants.
	 *
	 * @see EditEntity::attemptSave
	 * @see WikiPage::doEditContent
	 *
	 * @var int
	 */
	private $flags = 0;

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
		ApiBase $apiBase,
		EntityRevisionLookup $entityRevisionLookup,
		ApiErrorReporter $errorReporter,
		SummaryFormatter $summaryFormatter,
		EditEntityFactory $editEntityFactory
	) {
		parent::__construct( $entityRevisionLookup, $errorReporter );
		$this->apiBase = $apiBase;
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
	 * @return null|EntityFactory
	 */
	public function getEntityFactory() {
		return $this->entityFactory;
	}

	/**
	 * @param EntityFactory $entityFactory
	 */
	public function setEntityFactory( EntityFactory $entityFactory ) {
		$this->entityFactory = $entityFactory;
	}

	/**
	 * @return null|EntityStore
	 */
	public function getEntityStore() {
		return $this->entityStore;
	}

	/**
	 * @param EntityStore $entityStore
	 */
	public function setEntityStore( EntityFactory $entityStore ) {
		$this->entityStore = $entityStore;
	}

	/**
	 * Returns the given EntityDocument.
	 *
	 * @param EntityId|null $entityId ID of the entity to load. If not given, the ID is taken
	 *        from the request parameters. If $entityId is coven, it must be consistent with
	 *        the 'baserevid' parameter.
	 * @return EntityDocument
	 */
	public function loadEntity( EntityId $entityId = null ) {
		$params = $this->apiBase->extractRequestParams();

		if ( !$entityId ) {
			$entityId = $this->getEntityIdFromParams( $params );
		}

		// If a base revision is given, use if for consistency!
		$baseRev = isset( $params['baserevid'] )
			? (int)$params['baserevid']
			: $this->defaultRetrievalMode;

		if ( $entityId ) {
			$entityRevision = $this->loadEntityRevision( $entityId, $baseRev );
		} else {
			$entityRevision = null;
		}

		$new = isset( $params['new'] ) ? $params['new'] : null;
		if ( is_null( $entityRevision ) ) {
			if ( !$this->isEntityCreationSupported() ) {
				$this->errorReporter->dieError(
					'Could not find entity ' . $entityId,
					'no-such-entity'
				);
			}

			if ( $new ) {
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

			$entity = $this->createEntity( $new, $entityId );

			$this->flags = EDIT_NEW;
			$this->baseRevisionId = 0;
		} else {
			$this->flags = EDIT_UPDATE;
			$this->baseRevisionId = $entityRevision->getRevisionId();
			$entity = $entityRevision->getEntity();
		}

		return $entity;
	}

	/**
	 * @return bool
	 */
	private function isEntityCreationSupported() {
		return isset( $this->entityStore ) && isset( $this->entityFactory );
	}

	/**
	 * Create an empty entity.
	 *
	 * @since 0.1
	 *
	 * @param string|null $entityType The type of entity to create. Optional if an ID is given.
	 * @param EntityId|null $customId Optionally assigns a specific ID instead of generating a new
	 *  one.
	 *
	 * @throws InvalidArgumentException when entity type and ID are given but do not match.
	 * @throws UsageException
	 * @throws LogicException
	 * @return EntityDocument
	 */
	protected function createEntity( $entityType, EntityId $customId = null ) {
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
		} catch ( InvalidArgumentException $ex ) {
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
			// NOTE: We need to assign an ID early, for things like the ClaimIdGenerator.
			$this->entityStore->assignFreshId( $entity );
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
	 * @param string|Summary $summary The edit summary
	 * @param int $flags The edit flags (see WikiPage::doEditContent)
	 *
	 * @throws LogicException if not in write mode
	 * @return Status the status of the save operation, as returned by EditEntityHandler::attemptSave()
	 * @see  EditEntityHandler::attemptSave()
	 */
	public function attemptSaveEntity( EntityDocument $entity, $summary, $flags = 0 ) {
		if ( !$this->apiBase->isWriteMode() ) {
			// sanity/safety check
			throw new LogicException(
				'attemptSaveEntity() cannot be used by API modules that do not return true from isWriteMode()!'
			);
		}

		if ( $summary instanceof Summary ) {
			$summary = $this->summaryFormatter->formatSummary( $summary );
		}

		$params = $this->apiBase->extractRequestParams();
		$user = $this->apiBase->getContext()->getUser();

		if ( isset( $params['bot'] ) && $params['bot'] && $user->isAllowed( 'bot' ) ) {
			$flags |= EDIT_FORCE_BOT;
		}

		if ( !$this->baseRevisionId ) {
			$this->baseRevisionId = isset( $params['baserevid'] ) ? (int)$params['baserevid'] : null;
		}

		$editEntityHandler = $this->editEntityFactory->newEditEntity(
			$user,
			$entity,
			$this->baseRevisionId
		);

		$token = $this->evaluateTokenParam( $params );

		if ( isset( $params['bot'] ) && $params['bot'] ) {
			$flags |= ( $this->apiBase->getUser()->isAllowed( 'bot' ) ) ? EDIT_FORCE_BOT : 0;
		}

		$status = $editEntityHandler->attemptSave(
			$summary,
			$this->flags | $flags,
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
		if ( !$this->apiBase->needsToken() ) {
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

			if ( ( $editError & EditEntityHandler::TOKEN_ERROR ) > 0 ) {
				$errorCode = 'badtoken';
			} elseif ( ( $editError & EditEntityHandler::EDIT_CONFLICT_ERROR ) > 0 ) {
				$errorCode = 'editconflict';
			} elseif ( ( $editError & EditEntityHandler::ANY_ERROR ) > 0 ) {
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
	 * If $status->isOK() is false, this method will terminate with a UsageException.
	 *
	 * @param Status $status The status to report
	 * @param string  $errorCode The API error code to use in case $status->isOK() returns false
	 * @param array   $extradata Additional data to include the the error report,
	 *                if $status->isOK() returns false
	 * @param int     $httpRespCode the HTTP response code to use in case
	 *                $status->isOK() returns false.+
	 *
	 * @throws UsageException If $status->isOK() returns false.
	 */
	private function handleStatus(
		Status $status,
		$errorCode,
		array $extradata = array(),
		$httpRespCode = 0
	) {
		if ( $status->isGood() ) {
			return;
		} elseif ( $status->isOK() ) {
			$this->errorReporter->reportStatusWarnings( $status );
		} else {
			$this->errorReporter->reportStatusWarnings( $status );
			$this->errorReporter->dieStatus( $status, $errorCode, $httpRespCode, $extradata );
		}
	}

}
