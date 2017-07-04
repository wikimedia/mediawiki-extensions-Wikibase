<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\StatementRankSerializer;

/**
 * API module for getting claims.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Addshore
 */
class GetClaims extends ApiBase {

	/**
	 * @var StatementGuidValidator
	 */
	private $guidValidator;

	/**
	 * @var StatementGuidParser
	 */
	private $guidParser;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var EntityLoadingHelper
	 */
	private $entityLoadingHelper;

	/**
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * @see ApiBase::__construct
	 *
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param StatementGuidValidator $guidValidator
	 * @param StatementGuidParser $guidParser
	 * @param EntityIdParser $idParser
	 * @param ApiErrorReporter $errorReporter
	 * @param callable $resultBuilderInstantiator
	 * @param callable $entityLoadingHelperInstantiator
	 */
	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		StatementGuidValidator $guidValidator,
		StatementGuidParser $guidParser,
		EntityIdParser $idParser,
		ApiErrorReporter $errorReporter,
		callable $resultBuilderInstantiator,
		callable $entityLoadingHelperInstantiator
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->guidValidator = $guidValidator;
		$this->guidParser = $guidParser;
		$this->idParser = $idParser;
		$this->errorReporter = $errorReporter;
		$this->resultBuilder = $resultBuilderInstantiator( $this );
		$this->entityLoadingHelper = $entityLoadingHelperInstantiator( $this );
	}

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {
		$this->getMain()->setCacheMode( 'public' );

		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		list( $idString, $guid ) = $this->getIdentifiers( $params );

		try {
			$entityId = $this->idParser->parse( $idString );
		} catch ( EntityIdParsingException $e ) {
			$this->errorReporter->dieException( $e, 'param-invalid' );
		}

		/** @var EntityId $entityId */
		$entity = $this->entityLoadingHelper->loadEntity( $entityId );

		$statements = $this->getStatements( $entity, $guid );
		$this->resultBuilder->addStatements( $statements, null, $params['props'] );
	}

	private function validateParameters( array $params ) {
		if ( !isset( $params['entity'] ) && !isset( $params['claim'] ) ) {
			$this->errorReporter->dieError(
				'Either the entity parameter or the claim parameter need to be set',
				'param-missing'
			);
		}
	}

	/**
	 * @param EntityDocument $entity
	 * @param string|null $guid
	 *
	 * @return StatementList
	 */
	private function getStatements( EntityDocument $entity, $guid = null ) {
		if ( !( $entity instanceof StatementListProvider ) ) {
			return new StatementList();
		}

		$statements = $entity->getStatements();

		if ( $guid === null ) {
			return $statements->filter( $this->newRequestParamsBasedFilter() );
		}

		$statement = $statements->getFirstStatementWithGuid( $guid );
		return new StatementList( $statement === null ? [] : $statement );
	}

	private function newRequestParamsBasedFilter() {
		return new GetClaimsStatementFilter(
			$this->idParser,
			$this->errorReporter,
			$this->extractRequestParams()
		);
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
		$guid = null;

		if ( isset( $params['claim'] ) ) {
			$guid = $params['claim'];
			$idString = $this->getEntityIdFromStatementGuid( $params['claim'] );

			if ( isset( $params['entity'] ) && $idString !== $params['entity'] ) {
				$this->errorReporter->dieError(
					'If both entity id and claim key are provided they need to point to the same entity',
					'param-illegal'
				);
			}
		} else {
			$idString = $params['entity'];
		}

		return [ $idString, $guid ];
	}

	private function getEntityIdFromStatementGuid( $guid ) {
		if ( $this->guidValidator->validateFormat( $guid ) === false ) {
			$this->errorReporter->dieError( 'Invalid claim guid', 'invalid-guid' );
		}

		return $this->guidParser->parse( $guid )->getEntityId()->getSerialization();
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return [
			'entity' => [
				self::PARAM_TYPE => 'string',
			],
			'property' => [
				self::PARAM_TYPE => 'string',
			],
			'claim' => [
				self::PARAM_TYPE => 'string',
			],
			'rank' => [
				self::PARAM_TYPE => StatementRankSerializer::getRanks(),
			],
			'props' => [
				self::PARAM_TYPE => [
					'references',
				],
				self::PARAM_DFLT => 'references',
				self::PARAM_ISMULTI => true,
			],
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return [
			"action=wbgetclaims&entity=Q42" =>
				"apihelp-wbgetclaims-example-1",
			"action=wbgetclaims&entity=Q42&property=P31" =>
				"apihelp-wbgetclaims-example-2",
			"action=wbgetclaims&entity=Q42&rank=normal" =>
				"apihelp-wbgetclaims-example-3",
			'action=wbgetclaims&claim=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F' =>
				'apihelp-wbgetclaims-example-4',
		];
	}

}
