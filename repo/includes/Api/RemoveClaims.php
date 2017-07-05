<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * API module for removing claims.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class RemoveClaims extends ApiBase {

	/**
	 * @var StatementChangeOpFactory
	 */
	private $statementChangeOpFactory;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var StatementModificationHelper
	 */
	private $modificationHelper;

	/**
	 * @var StatementGuidParser
	 */
	private $guidParser;

	/**
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * @var EntitySavingHelper
	 */
	private $entitySavingHelper;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param ApiErrorReporter $errorReporter
	 * @param StatementChangeOpFactory $statementChangeOpFactory
	 * @param StatementModificationHelper $modificationHelper
	 * @param StatementGuidParser $guidParser
	 * @param callable $resultBuilderInstantiator
	 * @param callable $entitySavingHelperInstantiator
	 */
	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		ApiErrorReporter $errorReporter,
		StatementChangeOpFactory $statementChangeOpFactory,
		StatementModificationHelper $modificationHelper,
		StatementGuidParser $guidParser,
		callable $resultBuilderInstantiator,
		callable $entitySavingHelperInstantiator
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->errorReporter = $errorReporter;
		$this->statementChangeOpFactory = $statementChangeOpFactory;
		$this->modificationHelper = $modificationHelper;

		$this->guidParser = $guidParser;
		$this->resultBuilder = $resultBuilderInstantiator( $this );
		$this->entitySavingHelper = $entitySavingHelperInstantiator( $this );
	}

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$entityId = $this->getEntityId( $params );
		$entity = $this->entitySavingHelper->loadEntity( $entityId );

		if ( $entity instanceof StatementListProvider ) {
			$this->assertStatementListContainsGuids( $entity->getStatements(), $params['claim'] );
		}

		$summary = $this->modificationHelper->createSummary( $params, $this );

		$changeOps = new ChangeOps();
		$changeOps->add( $this->getChangeOps( $params ) );

		try {
			$changeOps->apply( $entity, $summary );
		} catch ( ChangeOpException $e ) {
			$this->errorReporter->dieException( $e, 'failed-save' );
		}

		$status = $this->entitySavingHelper->attemptSaveEntity( $entity, $summary );
		$this->resultBuilder->addRevisionIdFromStatusToResult( $status, 'pageinfo' );
		$this->resultBuilder->markSuccess();
		$this->resultBuilder->setList( null, 'claims', $params['claim'], 'claim' );
	}

	/**
	 * Validates the parameters and returns the EntityId to act upon on success
	 *
	 * @param array $params
	 *
	 * @return EntityId
	 */
	private function getEntityId( array $params ) {
		$entityId = null;

		foreach ( $params['claim'] as $guid ) {
			if ( !$this->modificationHelper->validateStatementGuid( $guid ) ) {
				$this->errorReporter->dieError( "Invalid claim guid $guid", 'invalid-guid' );
			}

			if ( is_null( $entityId ) ) {
				$entityId = $this->guidParser->parse( $guid )->getEntityId();
			} else {
				if ( !$this->guidParser->parse( $guid )->getEntityId()->equals( $entityId ) ) {
					$this->errorReporter->dieError( 'All claims must belong to the same entity', 'invalid-guid' );
				}
			}
		}

		if ( is_null( $entityId ) ) {
			$this->errorReporter->dieError( 'Could not find an entity for the claims', 'invalid-guid' );
		}

		return $entityId;
	}

	/**
	 * @param StatementList $statements
	 * @param string[] $requiredGuids
	 */
	private function assertStatementListContainsGuids( StatementList $statements, array $requiredGuids ) {
		$existingGuids = [];

		/** @var Statement $statement */
		foreach ( $statements as $statement ) {
			$guid = $statement->getGuid();
			// This array is used as a HashSet where only the keys are used.
			$existingGuids[$guid] = null;
		}

		// Not using array_diff but array_diff_key does have a huge performance impact.
		$missingGuids = array_diff_key( array_flip( $requiredGuids ), $existingGuids );

		if ( !empty( $missingGuids ) ) {
			$this->errorReporter->dieError(
				'Statement(s) with GUID(s) ' . implode( ', ', array_keys( $missingGuids ) ) . ' not found',
				'invalid-guid'
			);
		}
	}

	/**
	 * @param array $params
	 *
	 * @return ChangeOp[]
	 */
	private function getChangeOps( array $params ) {
		$changeOps = [];

		foreach ( $params['claim'] as $guid ) {
			$changeOps[] = $this->statementChangeOpFactory->newRemoveStatementOp( $guid );
		}

		return $changeOps;
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
			[
				'claim' => [
					self::PARAM_TYPE => 'string',
					self::PARAM_ISMULTI => true,
					self::PARAM_REQUIRED => true,
				],
				'summary' => [
					self::PARAM_TYPE => 'string',
				],
				'token' => null,
				'baserevid' => [
					self::PARAM_TYPE => 'integer',
				],
				'bot' => false,
			],
			parent::getAllowedParams()
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return [
			'action=wbremoveclaims&claim=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0N&token=foobar'
				. '&baserevid=7201010'
				=> 'apihelp-wbremoveclaims-example-1',
		];
	}

}
