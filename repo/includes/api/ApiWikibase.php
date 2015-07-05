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
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\EntityRevision;
use Wikibase\Lib\Localizer\ExceptionLocalizer;
use Wikibase\Lib\Store\BadRevisionException;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\UnresolvedRedirectException;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;
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
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var EntityPermissionChecker
	 */
	private $permissionChecker;

	/**
	 * @var EditFilterHookRunner
	 */
	private $editFilterHookRunner;

	/**
	 * @var EntitySaveHelper
	 */
	private $entitySaveHelper;

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

		$this->titleLookup = $wikibaseRepo->getEntityTitleLookup();
		$this->idParser = $wikibaseRepo->getEntityIdParser();

		// NOTE: use uncached lookup for write mode!
		$uncached = $this->isWriteMode() ? 'uncached' : '';
		$this->entityRevisionLookup = $wikibaseRepo->getEntityRevisionLookup( $uncached );

		$this->summaryFormatter = $wikibaseRepo->getSummaryFormatter();

		$this->permissionChecker = $wikibaseRepo->getEntityPermissionChecker();

		$this->exceptionLocalizer = $wikibaseRepo->getExceptionLocalizer();

		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $this->getContext() );
		$this->errorReporter = $apiHelperFactory->getErrorReporter( $this );
		$this->resultBuilder = $apiHelperFactory->getResultBuilder( $this );
		$this->entitySaveHelper = $apiHelperFactory->getEntitySaveHelper( $this );

		$this->editFilterHookRunner = new EditFilterHookRunner(
			$this->titleLookup,
			$wikibaseRepo->getEntityContentFactory(),
			$this->getContext()
		);
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
	 * @return ResultBuilder
	 */
	protected function getResultBuilder() {
		return $this->resultBuilder;
	}

	/**
	 * @see ApiBase::needsToken()
	 *
	 * @return string|false
	 */
	public function needsToken() {
		return $this->isWriteMode() ? 'csrf' : false;
	}

	/**
	 * @see ApiBase::getTokenSalt()
	 *
	 * @return string|false
	 */
	public function getTokenSalt() {
		return $this->needsToken() ? '' : false;
	}

	/**
	 * @see ApiBase::mustBePosted()
	 *
	 * @return bool
	 */
	public function mustBePosted() {
		return $this->isWriteMode();
	}

	/**
	 * @see ApiBase::isReadMode
	 *
	 * @return bool Always true in this abstract base implementation.
	 */
	public function isReadMode() {
		return true;
	}

	/**
	 * Returns the permissions that are required to perform the operation specified by
	 * the parameters.
	 *
	 * Per default, this will include the 'read' permission if $this->isReadMode() returns true,
	 * and the 'edit' permission if $this->isWriteMode() returns true,
	 *
	 * @param EntityDocument $entity The entity to check permissions for
	 *
	 * @return string[] A list of permissions
	 */
	protected function getRequiredPermissions( EntityDocument $entity ) {
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
	 * @param $entity EntityDocument the entity to check
	 * @param $user User doing the action
	 *
	 * @return Status the check's result
	 * @todo: use this also to check for read access in ApiGetEntities, etc
	 */
	protected function checkPermissions( EntityDocument $entity, User $user ) {
		$permissions = $this->getRequiredPermissions( $entity );
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
	 * cannot be found or cannot be loaded.
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId : the title of the page to load the revision for
	 * @param int|string $revId : the revision to load. If not given, the current revision will be loaded.
	 *
	 * @throws UsageException
	 * @throws LogicException
	 * @return EntityRevision
	 */
	protected function loadEntityRevision( EntityId $entityId, $revId = EntityRevisionLookup::LATEST_FROM_MASTER ) {
		try {
			$revision = $this->entityRevisionLookup->getEntityRevision( $entityId, $revId );

			if ( !$revision ) {
				$this->errorReporter->dieError(
					'Entity ' . $entityId->getSerialization() . ' not found',
					'cant-load-entity-content' );
			}

			return $revision;
		} catch ( UnresolvedRedirectException $ex ) {
			$this->errorReporter->dieException( $ex, 'unresolved-redirect' );
		} catch ( BadRevisionException $ex ) {
			$this->errorReporter->dieException( $ex, 'nosuchrevid' );
		} catch ( StorageException $ex ) {
			$this->errorReporter->dieException( $ex, 'cant-load-entity-content' );
		}

		throw new LogicException( 'ApiErrorReporter::dieError did not throw a UsageException' );
	}

	/**
	 * @see EntitySaveHelper::attemptSaveEntity
	 */
	protected function attemptSaveEntity( Entity $entity, $summary, $flags = 0 ) {
		return $this->entitySaveHelper->attemptSaveEntity( $entity, $summary, $flags );
	}

	/**
	 * @deprecated since 0.5, use dieError(), dieException() and dieMessage()
	 * methods inside an ApiErrorReporter object instead
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

}
