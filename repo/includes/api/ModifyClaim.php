<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Status;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * Base class for modifying claims.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class ModifyClaim extends ApiBase {

	/**
	 * @since 0.4
	 *
	 * @var StatementModificationHelper
	 */
	protected $modificationHelper;

	/**
	 * @since 0.5
	 *
	 * @var StatementGuidParser
	 */
	protected $guidParser;

	/**
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * @var EntityLoadingHelper
	 */
	private $entityLoadingHelper;

	/**
	 * @var EntitySavingHelper
	 */
	private $entitySavingHelper;

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

		$this->modificationHelper = new StatementModificationHelper(
			$wikibaseRepo->getSnakConstructionService(),
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getClaimGuidValidator(),
			$apiHelperFactory->getErrorReporter( $this )
		);

		$this->guidParser = WikibaseRepo::getDefaultInstance()->getStatementGuidParser();
		$this->resultBuilder = $apiHelperFactory->getResultBuilder( $this );
		$this->entityLoadingHelper = $apiHelperFactory->getEntityLoadingHelper( $this );
		$this->entitySavingHelper = $apiHelperFactory->getEntitySavingHelper( $this );
	}

	/**
	 * @see EntitySavingHelper::attemptSaveEntity
	 */
	protected function attemptSaveEntity( Entity $entity, $summary, $flags = 0 ) {
		return $this->entitySavingHelper->attemptSaveEntity( $entity, $summary, $flags );
	}

	/**
	 * @return ResultBuilder
	 */
	protected function getResultBuilder() {
		return $this->resultBuilder;
	}

	/**
	 * @see EntitySavingHelper::loadEntityRevision
	 */
	protected function loadEntityRevision(
		EntityId $entityId,
		$revId = EntityRevisionLookup::LATEST_FROM_MASTER
	) {
		return $this->entityLoadingHelper->loadEntityRevision( $entityId, $revId );
	}

	/**
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param Summary $summary
	 *
	 * @returns Status
	 */
	public function saveChanges( Entity $entity, Summary $summary ) {
		return $this->attemptSaveEntity(
			$entity,
			$summary,
			EDIT_UPDATE
		);
	}

	/**
	 * @see ApiBase::isWriteMode
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @see ApiBase::needsToken
	 *
	 * @return string
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			parent::getAllowedParams(),
			array(
				'summary' => array(
					self::PARAM_TYPE => 'string',
				),
				'token' => null,
				'baserevid' => array(
					self::PARAM_TYPE => 'integer',
				),
				'bot' => false,
			)
		);
	}

}
