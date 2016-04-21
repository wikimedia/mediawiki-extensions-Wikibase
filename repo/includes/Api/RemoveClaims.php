<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\StatementChangeOpFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for removing claims.
 *
 * @since 0.3
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
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $this->getContext() );
		$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

		$this->errorReporter = $apiHelperFactory->getErrorReporter( $this );
		$this->statementChangeOpFactory = $changeOpFactoryProvider->getStatementChangeOpFactory();

		$this->modificationHelper = new StatementModificationHelper(
			$wikibaseRepo->getSnakFactory(),
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getStatementGuidValidator(),
			$apiHelperFactory->getErrorReporter( $this )
		);

		$this->guidParser = $wikibaseRepo->getStatementGuidParser();
		$this->resultBuilder = $apiHelperFactory->getResultBuilder( $this );
		$this->entityLoadingHelper = $apiHelperFactory->getEntityLoadingHelper( $this );
		$this->entitySavingHelper = $apiHelperFactory->getEntitySavingHelper( $this );
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$entityId = $this->getEntityId( $params );
		if ( isset( $params['baserevid'] ) ) {
			$entityRevision = $this->entityLoadingHelper->loadEntityRevision(
				$entityId,
				(int)$params['baserevid']
			);
		} else {
			$entityRevision = $this->entityLoadingHelper->loadEntityRevision( $entityId );
		}
		$entity = $entityRevision->getEntity();

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

		$status = $this->entitySavingHelper->attemptSaveEntity( $entity, $summary, EDIT_UPDATE );
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
			array(
				'claim' => array(
					self::PARAM_TYPE => 'string',
					self::PARAM_ISMULTI => true,
					self::PARAM_REQUIRED => true,
				),
				'summary' => array(
					self::PARAM_TYPE => 'string',
				),
				'token' => null,
				'baserevid' => array(
					self::PARAM_TYPE => 'integer',
				),
				'bot' => false,
			),
			parent::getAllowedParams()
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wbremoveclaims&claim=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0N&token=foobar'
				. '&baserevid=7201010'
				=> 'apihelp-wbremoveclaims-example-1',
		);
	}

}
