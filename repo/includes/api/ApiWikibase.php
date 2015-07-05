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

		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $this->getContext() );
		$this->entitySaveHelper = $apiHelperFactory->getEntitySaveHelper( $this );
		$this->entityLoadHelper = $apiHelperFactory->getEntityLoadHelper( $this );
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
