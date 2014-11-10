<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use Exception;
use LogicException;
use Status;
use UsageException;
use User;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\EditEntity;
use Wikibase\EntityRevision;
use Wikibase\Lib\Localizer\ExceptionLocalizer;
use Wikibase\Lib\Store\BadRevisionException;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\UnresolvedRedirectException;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Base class for API modules
 *
 * @since 0.1
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Adam Shorland
 * @author Daniel Kinzler
 */
abstract class ApiWikibase extends ApiBase {

	/**
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var ExceptionLocalizer
	 */
	private $exceptionLocalizer;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var EntityPermissionChecker
	 */
	private $permissionChecker;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$this->titleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$this->idParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();

		// NOTE: use uncached lookup for write mode!
		$uncached = $this->isWriteMode() ? 'uncached' : '';
		$this->entityRevisionLookup = WikibaseRepo::getDefaultInstance()->getEntityRevisionLookup( $uncached );
		$this->entityStore = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$this->dataTypeLookup = WikibaseRepo::getDefaultInstance()->getPropertyDataTypeLookup();
		$this->summaryFormatter = WikibaseRepo::getDefaultInstance()->getSummaryFormatter();

		$this->permissionChecker = WikibaseRepo::getDefaultInstance()->getEntityPermissionChecker();

		$this->exceptionLocalizer = WikibaseRepo::getDefaultInstance()->getExceptionLocalizer();

		$this->errorReporter = WikibaseRepo::getDefaultInstance()->getApiHelperFactory()->getErrorReporter( $this );

		$this->resultBuilder = WikibaseRepo::getDefaultInstance()->getApiHelperFactory()->getResultBuilder( $this );
	}

	/**
	 * @return ApiErrorReporter
	 */
	protected function getErrorReporter() {
		return $this->errorReporter;
	}

	/**
	 * @return EntityStore
	 */
	protected function getEntityStore() {
		return $this->entityStore;
	}

	/**
	 * @return EntityRevisionLookup
	 */
	protected function getEntityRevisionLookup() {
		return $this->entityRevisionLookup;
	}

	/**
	 * @return EntityIdParser
	 */
	protected function getIdParser() {
		return $this->idParser;
	}

	/**
	 * @return EntityTitleLookup
	 */
	protected function getTitleLookup() {
		return $this->titleLookup;
	}

	/**
	 * @param Entity $entity
	 *
	 * @return bool
	 */
	protected function entityExists( Entity $entity ) {
		$entityId = $entity->getId();
		$title = $entityId === null ? null : $this->titleLookup->getTitleForId( $entityId );
		return ( $title !== null && $title->exists() );
	}

	/**
	 * @return ResultBuilder
	 */
	protected function getResultBuilder() {
		return $this->resultBuilder;
	}

	/**
	 * @see ApiBase::getParamDescription()
	 */
	public function getParamDescription() {
		return array(
		);
	}

	/**
	 * @see ApiBase::getAllowedParams()
	 */
	public function getAllowedParams() {
		return array(
		);
	}

	/**
	 * @see ApiBase::needsToken()
	 */
	public function needsToken() {
		return $this->isWriteMode() ? 'csrf' : false;
	}

	/**
	 * @see ApiBase::getTokenSalt()
	 */
	public function getTokenSalt() {
		return $this->needsToken() ? '' : false;
	}

	/**
	 * @see ApiBase::mustBePosted()
	 */
	public function mustBePosted() {
		return $this->isWriteMode();
	}

	/**
	 * @see ApiBase::isReadMode
	 */
	public function isReadMode() {
		return true;
	}

	/**
	 * @see ApiBase::getVersion
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getVersion() {
		return get_class( $this ) . '-' . WB_VERSION;
	}

	/**
	 * Returns the permissions that are required to perform the operation specified by
	 * the parameters.
	 *
	 * Per default, this will include the 'read' permission if $this->isReadMode() returns true,
	 * and the 'edit' permission if $this->isWriteMode() returns true,
	 *
	 * @param Entity $entity The entity to check permissions for
	 * @param array $params Arguments for the module, describing the operation to be performed
	 *
	 * @return array A list of permissions
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = array();

		if ( $this->isReadMode() ) {
			$permissions[] = 'read';
		}

		if ( $this->isWriteMode() ) {
			$permissions[] = 'edit';
		}

		return $permissions;
	}

	/**
	 * Check the rights for the user accessing the module.
	 *
	 * @param $entity Entity the entity to check
	 * @param $user User doing the action
	 * @param $params array of arguments for the module, passed for ModifyItem
	 *
	 * @return Status the check's result
	 * @todo: use this also to check for read access in ApiGetEntities, etc
	 */
	protected function checkPermissions( Entity $entity, User $user, array $params ) {
		$permissions = $this->getRequiredPermissions( $entity, $params );
		$status = Status::newGood();

		foreach ( array_unique( $permissions ) as $perm ) {
			$permStatus = $this->permissionChecker->getPermissionStatusForEntity( $user, $perm, $entity );
			$status->merge( $permStatus );
		}

		return $status;
	}

	/**
	 * Load the entity content of the given revision.
	 *
	 * Will fail by calling dieError() on the ApiErrorReporter if the revision
	 * can not be found or can not be loaded.
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId : the title of the page to load the revision for
	 * @param int $revId : the revision to load. If not given, the current revision will be loaded.
	 *
	 * @throws UsageException
	 * @throws LogicException
	 * @return EntityRevision
	 */
	protected function loadEntityRevision( EntityId $entityId, $revId = 0 ) {
		try {
			$revision = $this->entityRevisionLookup->getEntityRevision( $entityId, $revId );

			if ( !$revision ) {
				$this->dieError(
					'Entity ' . $entityId->getSerialization() . ' not found',
					'cant-load-entity-content' );
			}

			return $revision;
		} catch ( UnresolvedRedirectException $ex ) {
			$this->dieException( $ex, 'unresolved-redirect' );
		} catch ( BadRevisionException $ex ) {
			$this->dieException( $ex, 'nosuchrevid' );
		} catch ( StorageException $ex ) {
			$this->dieException( $ex, 'cant-load-entity-content' );
		}

		throw new LogicException( 'ApiErrorReporter::dieError did not throw a UsageException' );
	}

	/**
	 * Signal errors and warnings from a save operation to the API call's output.
	 * This is much like handleStatus(), but specialized for Status objects returned by
	 * EditEntity::attemptSave(). In particular, the 'errorFlags' and 'errorCode' fields
	 * from the status value are used to determine the error code to return to the caller.
	 *
	 * @note: this function may or may not return normally, depending on whether
	 *        the status is fatal or not.
	 *
	 * @see handleStatus().
	 *
	 * @param Status $status The status to report
	 */
	protected function handleSaveStatus( Status $status ) {
		$value = $status->getValue();
		$errorCode = null;

		if ( is_array( $value ) && isset( $value['errorCode'] ) ) {
			$errorCode = $value['errorCode'];
		} else {
			$editError = 0;

			if ( is_array( $value ) && isset( $value['errorFlags'] ) ) {
				$editError = $value['errorFlags'];
			}

			if ( ( $editError & EditEntity::TOKEN_ERROR ) > 0 ) {
				$errorCode = 'badtoken';
			} elseif ( ( $editError & EditEntity::EDIT_CONFLICT_ERROR ) > 0 ) {
				$errorCode = 'editconflict';
			} elseif ( ( $editError & EditEntity::ANY_ERROR ) > 0 ) {
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
	private function handleStatus( Status $status, $errorCode, array $extradata = array(), $httpRespCode = 0 ) {
		wfProfileIn( __METHOD__ );

		if ( $status->isGood() ) {
			return;
		} elseif ( $status->isOK() ) {
			$this->errorReporter->reportStatusWarnings( $status );
		} else {
			$this->errorReporter->reportStatusWarnings( $status );
			$this->errorReporter->dieStatus( $status, $errorCode, $httpRespCode, $extradata );
		}

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Attempts to save the new entity content, chile first checking for permissions,
	 * edit conflicts, etc. Saving is done via EditEntity::attemptSave().
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
	 * @param Entity $entity The entity to save
	 * @param string|Summary $summary The edit summary
	 * @param int $flags The edit flags (see WikiPage::doEditContent)
	 *
	 * @throws LogicException if not in write mode
	 * @return Status the status of the save operation, as returned by EditEntity::attemptSave()
	 * @see  EditEntity::attemptSave()
	 *
	 * @todo: this could be factored out into a controller-like class, but that controller
	 *        would still need knowledge of the API module to be useful. We'll put it here
	 *        for now pending further discussion.
	 */
	protected function attemptSaveEntity( Entity $entity, $summary, $flags = 0 ) {
		if ( !$this->isWriteMode() ) {
			// sanity/safety check
			throw new LogicException( 'attemptSaveEntity() can not be used by API modules that do not return true from isWriteMode()!' );
		}

		if ( $summary instanceof Summary ) {
			$summary = $this->formatSummary( $summary );
		}

		$params = $this->extractRequestParams();
		$user = $this->getUser();

		if ( isset( $params['bot'] ) && $params['bot'] && $user->isAllowed( 'bot' ) ) {
			$flags |= EDIT_FORCE_BOT;
		}

		$baseRevisionId = $this->evaluateBaseRevisionParam( $params );

		$editEntity = new EditEntity(
			$this->titleLookup,
			$this->entityRevisionLookup,
			$this->entityStore,
			$this->permissionChecker,
			$entity,
			$user,
			$baseRevisionId,
			$this->getContext()
		);

		$token = $this->evaluateTokenParam( $params );

		$status = $editEntity->attemptSave(
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
	 * @return false|null|string
	 */
	private function evaluateTokenParam( array $params ) {
		if ( !$this->needsToken() ) {
			// false disabled the token check
			$token = false;
		} else {
			// null fails the token check
			$token = isset( $params['token'] ) ? $params['token'] : null;
		}

		return $token;
	}

	/**
	 * @param array $params
	 *
	 * @return null|false|int
	 */
	private function evaluateBaseRevisionParam( array $params ) {
		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$baseRevisionId = $baseRevisionId > 0 ? $baseRevisionId : false;

		return $baseRevisionId;
	}

	/**
	 * @param Summary $summary
	 *
	 * @return string
	 */
	protected function formatSummary( Summary $summary ) {
		$formatter = $this->summaryFormatter;
		return $formatter->formatSummary( $summary );
	}

	/**
	 * Returns a Status object representing the given exception using a localized message.
	 *
	 * @note: The returned Status will always be fatal, that is, $status->isOk() will return false.
	 *
	 * @see getExceptionMessage().
	 *
	 * @param Exception $error
	 *
	 * @return Status
	 */
	protected function getExceptionStatus( Exception $error ) {
		$msg = $this->exceptionLocalizer->getExceptionMessage( $error );
		$status = Status::newFatal( $msg );
		$status->setResult( false, $error->getMessage() );

		return $status;
	}

	/**
	 * @deprecated since 0.5, use dieError(), dieException() or the
	 * methods in $this->apiErrorReporter instead.
	 *
	 * @param string $description
	 * @param string $errorCode
	 * @param int $httpRespCode
	 * @param null $extradata
	 */
	public function dieUsage( $description, $errorCode, $httpRespCode = 0, $extradata = null ) {
		//NOTE: This is just here for the @deprecated flag above.
		parent::dieUsage( $description, $errorCode, $httpRespCode, $extradata );
	}

	/**
	 * @see ApiErrorReporter::dieError()
	 *
	 * @since 0.5
	 *
	 * @param string $description
	 * @param string $code
	 * @param int $httpStatusCode
	 * @param array $extradata
	 */
	protected function dieError( $description, $code, $httpStatusCode = 0, $extradata = array() ) {
		$this->errorReporter->dieError( $description, $code, $httpStatusCode, $extradata );
	}

	/**
	 * @see ApiErrorReporter::dieException()
	 *
	 * @since 0.5
	 *
	 * @param Exception $exception
	 * @param string $code
	 * @param int $httpStatusCode
	 * @param array $extradata
	 */
	protected function dieException( Exception $exception, $code, $httpStatusCode = 0, $extradata = array() ) {
		$this->errorReporter->dieException( $exception, $code, $httpStatusCode, $extradata );
	}

	/**
	 * @see ApiErrorReporter::dieMessage()
	 *
	 * @since 0.5
	 *
	 * @param string $errorCode A code identifying the error.
	 * @param string ... Parameters for the Message.
	 */
	protected function dieMessage( $errorCode ) {
		call_user_func_array( array( $this->errorReporter, 'dieMessage' ), func_get_args() );
	}

}
