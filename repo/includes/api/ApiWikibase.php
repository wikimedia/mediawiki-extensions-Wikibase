<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\WikibaseRepo;

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
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntitySaveHelper
	 */
	private $entitySaveHelper;

	/**
	 * @var EntityLoadHelper
	 */
	private $entityLoadHelper;

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

		$this->idParser = $wikibaseRepo->getEntityIdParser();

		// NOTE: use uncached lookup for write mode!
		$uncached = $this->isWriteMode() ? 'uncached' : '';
		$this->entityRevisionLookup = $wikibaseRepo->getEntityRevisionLookup( $uncached );

		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $this->getContext() );
		$this->resultBuilder = $apiHelperFactory->getResultBuilder( $this );
		$this->entitySaveHelper = $apiHelperFactory->getEntitySaveHelper( $this );
		$this->entityLoadHelper = $apiHelperFactory->getEntityLoadHelper( $this );
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
	 * @see EntitySaveHelper::loadEntityRevision
	 */
	protected function loadEntityRevision(
		EntityId $entityId,
		$revId = EntityRevisionLookup::LATEST_FROM_MASTER
	) {
		return $this->entityLoadHelper->loadEntityRevision( $entityId, $revId );
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
