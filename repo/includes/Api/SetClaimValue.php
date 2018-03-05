<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;

/**
 * API module for setting the DataValue contained by the main snak of a claim.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SetClaimValue extends ApiBase {

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
		$this->validateParameters( $params );

		$this->logFeatureUsage( 'action=wbsetclaimvalue' );

		$guid = $params['claim'];
		$entityId = $this->guidParser->parse( $guid )->getEntityId();
		$entity = $this->entitySavingHelper->loadEntity( $entityId );

		$claim = $this->modificationHelper->getStatementFromEntity( $guid, $entity );

		$snak = $this->modificationHelper->getSnakInstance( $params, $claim->getPropertyId() );

		$summary = $this->modificationHelper->createSummary( $params, $this );

		$changeOp = $this->statementChangeOpFactory->newSetMainSnakOp( $guid, $snak );

		$this->modificationHelper->applyChangeOp( $changeOp, $entity, $summary );

		$status = $this->entitySavingHelper->attemptSaveEntity( $entity, $summary );
		$this->resultBuilder->addRevisionIdFromStatusToResult( $status, 'pageinfo' );
		$this->resultBuilder->markSuccess();
		$this->resultBuilder->addStatement( $claim );
	}

	/**
	 * @param array $params
	 */
	private function validateParameters( array $params ) {
		if ( !( $this->modificationHelper->validateStatementGuid( $params['claim'] ) ) ) {
			$this->errorReporter->dieError( 'Invalid claim guid', 'invalid-guid' );
		}
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
					self::PARAM_REQUIRED => true,
				],
				'value' => [
					self::PARAM_TYPE => 'text',
					self::PARAM_REQUIRED => false,
				],
				'snaktype' => [
					self::PARAM_TYPE => [ 'value', 'novalue', 'somevalue' ],
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
			'action=wbsetclaimvalue&claim=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F&snaktype=value'
				. '&value={"entity-type":"item","numeric-id":1}&token=foobar&baserevid=7201010'
				=> 'apihelp-wbsetclaimvalue-example-1',
		];
	}

}
