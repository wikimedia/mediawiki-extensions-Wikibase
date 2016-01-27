<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\WikibaseRepo;

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
	protected $resultBuilder;

	/**
	 * @var EntityLoadingHelper
	 */
	protected $entityLoadingHelper;

	/**
	 * @var EntitySavingHelper
	 */
	protected $entitySavingHelper;

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
			$wikibaseRepo->getStatementGuidValidator(),
			$apiHelperFactory->getErrorReporter( $this )
		);

		$this->guidParser = $wikibaseRepo->getStatementGuidParser();
		$this->resultBuilder = $apiHelperFactory->getResultBuilder( $this );
		$this->entityLoadingHelper = $apiHelperFactory->getEntityLoadingHelper( $this );
		$this->entitySavingHelper = $apiHelperFactory->getEntitySavingHelper( $this );
	}

}
