<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;

/**
 * API module for removing one or more references of the same statement.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class RemoveReferences extends ApiBase {

	use FederatedPropertyApiValidatorTrait;

	/**
	 * @var StatementChangeOpFactory
	 */
	private $statementChangeOpFactory;

	/**
	 * @var ApiErrorReporter
	 */
	protected $errorReporter;

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

	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		ApiErrorReporter $errorReporter,
		StatementChangeOpFactory $statementChangeOpFactory,
		StatementModificationHelper $modificationHelper,
		StatementGuidParser $guidParser,
		callable $resultBuilderInstantiator,
		callable $entitySavingHelperInstantiator,
		bool $federatedPropertiesEnabled
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->errorReporter = $errorReporter;
		$this->statementChangeOpFactory = $statementChangeOpFactory;
		$this->modificationHelper = $modificationHelper;
		$this->guidParser = $guidParser;
		$this->resultBuilder = $resultBuilderInstantiator( $this );
		$this->entitySavingHelper = $entitySavingHelperInstantiator( $this );
		$this->federatedPropertiesEnabled = $federatedPropertiesEnabled;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		$guid = $params['statement'];
		$entityId = $this->guidParser->parse( $guid )->getEntityId();

		$this->validateAlteringEntityById( $entityId );

		$entity = $this->entitySavingHelper->loadEntity( $entityId );
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

		$status = $this->entitySavingHelper->attemptSaveEntity( $entity, $summary );
		$this->resultBuilder->addRevisionIdFromStatusToResult( $status, 'pageinfo' );
		$this->resultBuilder->markSuccess();
	}

	/**
	 * @param array $params
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
	 * @inheritDoc
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
	 * @inheritDoc
	 */
	protected function getAllowedParams() {
		return array_merge(
			[
				'statement' => [
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => true,
				],
				'references' => [
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => true,
					self::PARAM_ISMULTI => true,
				],
				'summary' => [
					self::PARAM_TYPE => 'string',
				],
				'tags' => [
					self::PARAM_TYPE => 'tags',
					self::PARAM_ISMULTI => true,
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
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		return [
			'action=wbremovereferences&statement=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F'
				. '&references=455481eeac76e6a8af71a6b493c073d54788e7e9&token=foobar'
				. '&baserevid=7201010'
				=> 'apihelp-wbremovereferences-example-1',
		];
	}

}
