<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use Status;
use Wikibase\DataModel\Statement\StatementGuidParser;
use Wikibase\DataModel\Entity\Entity;
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
abstract class ModifyClaim extends ApiWikibase {

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
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$this->modificationHelper = new StatementModificationHelper(
			WikibaseRepo::getDefaultInstance()->getSnakConstructionService(),
			WikibaseRepo::getDefaultInstance()->getEntityIdParser(),
			WikibaseRepo::getDefaultInstance()->getClaimGuidValidator(),
			$this->getErrorReporter()
		);

		$this->guidParser = WikibaseRepo::getDefaultInstance()->getStatementGuidParser();
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
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			parent::getAllowedParams(),
			array(
				'summary' => array( ApiBase::PARAM_TYPE => 'string' ),
				'token' => null,
				'baserevid' => array(
					ApiBase::PARAM_TYPE => 'integer',
				),
				'bot' => false,
			)
		);
	}

}
