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
use Wikibase\EditEntity;
use Wikibase\EntityFactory;
use Wikibase\EntityPermissionChecker;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\EntityTitleLookup;
use Wikibase\Lib\Localizer\ExceptionLocalizer;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StorageException;
use Wikibase\Lib\Store\EntityStore;
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
	 * @since 0.5
	 *
	 * @var ResultBuilder
	 */
	protected $resultBuilder;

	/**
	 * @since 0.5
	 *
	 * @var ApiErrorReporter
	 */
	protected $errorReporter;

	/**
	 * @since 0.5
	 *
	 * @var ExceptionLocalizer
	 */
	protected $exceptionLocalizer;

	/**
	 * @since 0.5
	 *
	 * @var EntityTitleLookup
	 */
	protected $titleLookup;

	/**
	 * @since 0.5
	 *
	 * @var EntityIdParser
	 */
	protected $idParser;

	/**
	 * @since 0.5
	 *
	 * @var EntityRevisionLookup
	 */
	protected $entityLookup;

	/**
	 * @since 0.5
	 *
	 * @var EntityStore
	 */
	protected $entityStore;

	/**
	 * @since 0.5
	 *
	 * @var PropertyDataTypeLookup
	 */
	protected $dataTypeLookup;

	/**
	 * @since 0.5
	 *
	 * @var SummaryFormatter
	 */
	protected $summaryFormatter;

	/**
	 * @since 0.5
	 *
	 * @var EntityPermissionChecker
	 */
	protected $permissionChecker;

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
		$uncachedFlag = $this->isWriteMode() ? 'uncached' : '';
		$this->entityLookup = WikibaseRepo::getDefaultInstance()->getEntityRevisionLookup( $uncachedFlag );
		$this->entityStore = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$this->dataTypeLookup = WikibaseRepo::getDefaultInstance()->getPropertyDataTypeLookup();
		$this->summaryFormatter = WikibaseRepo::getDefaultInstance()->getSummaryFormatter();

		$this->permissionChecker = WikibaseRepo::getDefaultInstance()->getEntityPermissionChecker();

		$this->exceptionLocalizer = WikibaseRepo::getDefaultInstance()->getExceptionLocalizer();

		$this->errorReporter = new ApiErrorReporter(
			$this,
			$this->exceptionLocalizer,
			$this->getContext()->getLanguage()
		);
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
	public function getResultBuilder() {
		if( !isset( $this->resultBuilder ) ) {

			$serializerFactory = new SerializerFactory(
				null,
				$this->dataTypeLookup,
				EntityFactory::singleton()
			);

			$this->resultBuilder = new ResultBuilder(
				$this->getResult(),
				$this->titleLookup,
				$serializerFactory
			);
		}
		return $this->resultBuilder;
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array(
			array( 'code' => 'failed-save', 'info' => $this->msg( 'wikibase-api-failed-save' )->text()  ),
			array( 'code' => 'editconflict', 'info' => $this->msg( 'wikibase-api-editconflict' )->text()  ),
			array( 'code' => 'badtoken', 'info' => $this->msg( 'wikibase-api-badtoken' )->text()  ),
			array( 'code' => 'nosuchrevid', 'info' => $this->msg( 'wikibase-api-nosuchrevid' )->text()  ),
			array( 'code' => 'cant-load-entity-content', 'info' => $this->msg( 'wikibase-api-cant-load-entity-content' )->text()  ),
		);
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
		return $this->isWriteMode();
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
	public function checkPermissions( Entity $entity, User $user, array $params ) {
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
			$revision = $this->entityLookup->getEntityRevision( $entityId, $revId );

			if ( !$revision ) {
				$this->errorReporter->dieError(
					"Can't access item content of "
						. $entityId->getSerialization()
						. ", revision may have been deleted.",
					'cant-load-entity-content' );
			}

			return $revision;
		} catch ( StorageException $ex ) {
			$this->errorReporter->dieError( "Revision $revId not found: " . $ex->getMessage(), 'nosuchrevid' );
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
	public function handleStatus( Status $status, $errorCode, array $extradata = array(), $httpRespCode = 0 ) {
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
			$this->entityLookup,
			$this->entityStore,
			$this->permissionChecker,
			$entity,
			$user,
			$baseRevisionId,
			$this->getContext() );

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
	protected function evaluateTokenParam( array $params ) {
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
	protected function evaluateBaseRevisionParam( array $params ) {
		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$baseRevisionId = $baseRevisionId > 0 ? $baseRevisionId : false;

		return $baseRevisionId;
	}

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
	 * @todo: Remove all usages of this!
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
	 * @param Exception $exception
	 * @param string $code
	 * @param int $httpStatusCode
	 * @param array $extradata
	 */
	protected function dieException( Exception $exception, $code, $httpStatusCode = 0, $extradata = array() ) {
		$this->errorReporter->dieException( $exception, $code, $httpStatusCode, $extradata );
	}

}
