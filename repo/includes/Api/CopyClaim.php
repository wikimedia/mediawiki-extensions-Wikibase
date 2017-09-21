<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use Wikibase\ClaimSummaryBuilder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Summary;

/**
 * API module for duplicating claims.
 *
 * @license GPL-2.0+
 */
class CopyClaim extends ApiBase {

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
	 * @var StatementGuidValidator
	 */
	private $guidValidator;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * @var EntitySavingHelper
	 */
	private $entitySavingHelper;

	/**
	 * @var EntityLoadingHelper
	 */
	private $entityLoadingHelper;

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
		StatementGuidValidator $guidValidator,
		StatementGuidParser $guidParser,
		EntityIdParser $idParser,
		callable $resultBuilderInstantiator,
		callable $entitySavingHelperInstantiator,
		callable $entityLoadingHelperInstantiator
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->errorReporter = $errorReporter;
		$this->statementChangeOpFactory = $statementChangeOpFactory;
		$this->modificationHelper = $modificationHelper;
		$this->guidValidator = $guidValidator;
		$this->guidParser = $guidParser;
		$this->idParser = $idParser;
		$this->resultBuilder = $resultBuilderInstantiator( $this );
		$this->entitySavingHelper = $entitySavingHelperInstantiator( $this );
		$this->entityLoadingHelper = $entityLoadingHelperInstantiator( $this );
	}

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		list( $idString, $guid ) = $this->getIdentifiers( $params );

		try {
			$entityId = $this->idParser->parse( $idString );
		} catch ( EntityIdParsingException $e ) {
			$this->errorReporter->dieException( $e, 'param-invalid' );
		}

		/** @var EntityId $entityId */
		$entity = $this->entityLoadingHelper->loadEntity( $entityId );
		$statements = $this->getStatements( $entity, $guid );

		if ( $statements->count() !== 1 ) {
			$this->errorReporter->dieError( 'The given guid can not be found', 'param-invalid' );
		}

		$statement = clone $statements->toArray()[0];
		$statement->setGuid( null );

		$newEntityId = $this->idParser->parse( $params['entity'] );
		$newEntity = $this->entitySavingHelper->loadEntity( $newEntityId );
		if ( !( $newEntity instanceof StatementListProvider ) ) {
			$this->errorReporter->dieError( 'The given entity cannot contain statements', 'not-supported' );
		}
		var_dump( 'ff' );
		$summary = $this->getSummary( $params, $statement, $newEntity->getStatements() );
		var_dump( 'hg' );
		$changeop = $this->statementChangeOpFactory->newSetStatementOp( $statement );

		$this->modificationHelper->applyChangeOp( $changeop, $newEntity, $summary );

		$status = $this->entitySavingHelper->attemptSaveEntity( $newEntity, $summary );
		$this->resultBuilder->addRevisionIdFromStatusToResult( $status, 'pageinfo' );
		$this->resultBuilder->markSuccess();
		$this->resultBuilder->addStatement( $statement );

		$stats = MediaWikiServices::getInstance()->getStatsdDataFactory();
		$stats->increment( 'wikibase.repo.api.wbcopyclaim.total' );
	}

	/**
	 * @param array $params
	 * @param Statement $statement
	 * @param StatementList $statementList
	 *
	 * @throws InvalidArgumentException
	 * @return Summary
	 */
	private function getSummary(
		array $params,
		Statement $statement,
		StatementList $statementList
	) {
		$claimSummaryBuilder = new ClaimSummaryBuilder(
			$this->getModuleName(),
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) )
		);

		$summary = $claimSummaryBuilder->buildClaimSummary(
			$statementList->getFirstStatementWithGuid( $statement->getGuid() ),
			$statement
		);

		if ( isset( $params['summary'] ) ) {
			$summary->setUserSummary( $params['summary'] );
		}

		return $summary;
	}

	/**
	 * Obtains the id of the entity for which to obtain claims and the claim GUID
	 * in case it was also provided.
	 *
	 * @param array $params
	 *
	 * @return array
	 * First element is a prefixed entity id string.
	 * Second element is either null or a statements GUID.
	 */
	private function getIdentifiers( array $params ) {
		$idString = $this->getEntityIdFromStatementGuid( $params['guid'] );
		var_dump( $idString );

		if ( isset( $params['entity'] ) && $idString === $params['entity'] ) {
			$this->errorReporter->dieWithError(
				'The guid should not belong to the same entity it is going to add',
				'param-illegal'
			);
		}


		return [ $idString, $params['guid'] ];
	}

	private function getEntityIdFromStatementGuid( $guid ) {
		if ( $this->guidValidator->validateFormat( $guid ) === false ) {
			$this->errorReporter->dieError( 'Invalid claim guid', 'invalid-guid' );
		}

		return $this->guidParser->parse( $guid )->getEntityId()->getSerialization();
	}

	/**
	 * @param EntityDocument $entity
	 * @param string $guid
	 *
	 * @return StatementList
	 */
	private function getStatements( EntityDocument $entity, $guid ) {
		if ( !( $entity instanceof StatementListProvider ) ) {
			return new StatementList();
		}

		$statements = $entity->getStatements();

		$statement = $statements->getFirstStatementWithGuid( $guid );
		return new StatementList( $statement === null ? [] : $statement );
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
				'guid' => [
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => true,
				],
				'entity' => [
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => true,
				],
				'summary' => [
					self::PARAM_TYPE => 'string',
				],
				'token' => null,
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
			'action=wbsetclaim&claim={"id":"Q2$5627445f-43cb-ed6d-3adb-760e85bd17ee",'
			. '"type":"claim","mainsnak":{"snaktype":"value","property":"P1",'
			. '"datavalue":{"value":"City","type":"string"}}}'
			=> 'apihelp-wbsetclaim-example-1',
			'action=wbsetclaim&claim={"id":"Q2$5627445f-43cb-ed6d-3adb-760e85bd17ee",'
			. '"type":"claim","mainsnak":{"snaktype":"value","property":"P1",'
			. '"datavalue":{"value":"City","type":"string"}}}&index=0'
			=> 'apihelp-wbsetclaim-example-2',
			'action=wbsetclaim&claim={"id":"Q2$5627445f-43cb-ed6d-3adb-760e85bd17ee",'
			. '"type":"statement","mainsnak":{"snaktype":"value","property":"P1",'
			. '"datavalue":{"value":"City","type":"string"}},'
			. '"references":[{"snaks":{"P2":[{"snaktype":"value","property":"P2",'
			. '"datavalue":{"value":"The Economy of Cities","type":"string"}}]},'
			. '"snaks-order":["P2"]}],"rank":"normal"}'
			=> 'apihelp-wbsetclaim-example-3',
		];
	}

}