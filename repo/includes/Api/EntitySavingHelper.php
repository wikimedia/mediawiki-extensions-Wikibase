<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use LogicException;
use Status;
use UsageException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EditEntity as EditEntityHandler;
use Wikibase\EditEntityFactory;
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
class EntitySavingHelper {

	/**
	 * @var ApiBase
	 */
	private $apiBase;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var EditEntityFactory
	 */
	private $editEntityFactory;

	public function __construct(
		ApiBase $apiBase,
		ApiErrorReporter $errorReporter,
		SummaryFormatter $summaryFormatter,
		EditEntityFactory $editEntityFactory
	) {
		$this->apiBase = $apiBase;
		$this->errorReporter = $errorReporter;
		$this->summaryFormatter = $summaryFormatter;
		$this->editEntityFactory = $editEntityFactory;
	}

	/**
	 * Create an empty entity.
	 *
	 * @param string|null $entityType The type of entity to be created (ignored if $id is given)
	 * @param EntityId|null $id The ID of the entity to be created (optional if $entityType is
	 *        given)
	 *
	 * @throws UsageException
	 * @throws LogicException
	 * @return EntityDocument Newly created entity
	 */
	public function newEntity( $entityType, EntityId $id = null ) {
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
	 * Attempts to save the new entity content, chile first checking for permissions,
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

		$baseRevisionId = isset( $params['baserevid'] ) ? (int)$params['baserevid'] : null;

		$editEntityHandler = $this->editEntityFactory->newEditEntity(
			$user,
			$entity,
			$baseRevisionId
		);

		$token = $this->evaluateTokenParam( $params );

		$status = $editEntityHandler->attemptSave(
			$summary,
			$flags,
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
