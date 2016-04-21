<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\StatementChangeOpFactory;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for removing one or more references of the same statement.
 *
 * @since 0.3
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class RemoveReferences extends ApiBase {

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
		$this->validateParameters( $params );

		$guid = $params['statement'];
		$entityId = $this->guidParser->parse( $guid )->getEntityId();
		if ( isset( $params['baserevid'] ) ) {
			$entityRevision = $this->entityLoadingHelper->loadEntityRevision(
				$entityId,
				(int)$params['baserevid']
			);
		} else {
			$entityRevision = $this->entityLoadingHelper->loadEntityRevision( $entityId );
		}
		$entity = $entityRevision->getEntity();
		$summary = $this->modificationHelper->createSummary( $params, $this );

		$claim = $this->modificationHelper->getStatementFromEntity( $guid, $entity );

		if ( !( $claim instanceof Statement ) ) {
			$this->errorReporter->dieError(
				'The referenced claim is not a statement and thus cannot have references',
				'not-statement'
			);
		}

		$referenceHashes = $this->getReferenceHashesFromParams( $params, $claim );

		$changeOps = new ChangeOps();
		$changeOps->add( $this->getChangeOps( $guid, $referenceHashes ) );

		try {
			$changeOps->apply( $entity, $summary );
		} catch ( ChangeOpException $e ) {
			$this->errorReporter->dieException( $e, 'failed-save' );
		}

		$status = $this->entitySavingHelper->attemptSaveEntity( $entity, $summary, EDIT_UPDATE );
		$this->resultBuilder->addRevisionIdFromStatusToResult( $status, 'pageinfo' );
		$this->resultBuilder->markSuccess();
	}

	/**
	 * Check the provided parameters
	 */
	private function validateParameters( array $params ) {
		if ( !( $this->modificationHelper->validateStatementGuid( $params['statement'] ) ) ) {
			$this->errorReporter->dieError( 'Invalid claim guid', 'invalid-guid' );
		}
	}

	/**
	 * @param string $guid
	 * @param string[] $referenceHashes
	 *
	 * @return ChangeOp[]
	 */
	private function getChangeOps( $guid, array $referenceHashes ) {
		$changeOps = [];

		foreach ( $referenceHashes as $referenceHash ) {
			$changeOps[] = $this->statementChangeOpFactory->newRemoveReferenceOp( $guid, $referenceHash );
		}

		return $changeOps;
	}

	/**
	 * @param array $params
	 * @param Statement $statement
	 *
	 * @return string[]
	 */
	private function getReferenceHashesFromParams( array $params, Statement $statement ) {
		$references = $statement->getReferences();
		$hashes = [];

		foreach ( array_unique( $params['references'] ) as $referenceHash ) {
			if ( !$references->hasReferenceHash( $referenceHash ) ) {
				$this->errorReporter->dieError( 'Invalid reference hash', 'no-such-reference' );
			}
			$hashes[] = $referenceHash;
		}

		return $hashes;
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
				'statement' => array(
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => true,
				),
				'references' => array(
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => true,
					self::PARAM_ISMULTI => true,
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
			'action=wbremovereferences&statement=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F'
				. '&references=455481eeac76e6a8af71a6b493c073d54788e7e9&token=foobar'
				. '&baserevid=7201010'
				=> 'apihelp-wbremovereferences-example-1',
		);
	}

}
