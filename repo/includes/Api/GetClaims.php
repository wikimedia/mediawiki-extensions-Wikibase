<?php

declare( strict_types = 1 );

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
use Wikibase\Repo\StatementRankSerializer;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API module for getting claims.
 *
 * @license GPL-2.0-or-later
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
		string $moduleName,
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

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		ApiHelperFactory $apiHelperFactory,
		EntityIdParser $entityIdParser,
		StatementGuidParser $statementGuidParser,
		StatementGuidValidator $statementGuidValidator
	): self {
		return new self(
			$mainModule,
			$moduleName,
			$statementGuidValidator,
			$statementGuidParser,
			$entityIdParser,
			$apiHelperFactory->getErrorReporter( $mainModule ),
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getResultBuilder( $module );
			},
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getEntityLoadingHelper( $module );
			}
		);
	}

	/**
	 * @inheritDoc
	 */
	public function execute(): void {
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
		$entity = $this->entityLoadingHelper->loadEntity( $params, $entityId );

		$statements = $this->getStatements( $entity, $guid );
		$this->resultBuilder->addStatements( $statements, null, $params['props'] );
	}

	private function validateParameters( array $params ): void {
		if ( !isset( $params['entity'] ) && !isset( $params['claim'] ) ) {
			$this->errorReporter->dieError(
				'Either the entity parameter or the claim parameter need to be set',
				'param-missing'
			);
		}
	}

	private function getStatements( EntityDocument $entity, ?string $guid ): StatementList {
		if ( !( $entity instanceof StatementListProvider ) ) {
			return new StatementList();
		}

		$statements = $entity->getStatements();

		if ( $guid === null ) {
			return $statements->filter( $this->newRequestParamsBasedFilter() );
		}

		$statement = $statements->getFirstStatementWithGuid( $guid );
		return $statement === null ? new StatementList() : new StatementList( $statement );
	}

	private function newRequestParamsBasedFilter(): GetClaimsStatementFilter {
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
	private function getIdentifiers( array $params ): array {
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

	private function getEntityIdFromStatementGuid( string $guid ): string {
		if ( $this->guidValidator->validateFormat( $guid ) === false ) {
			$this->errorReporter->dieError( 'Invalid claim guid', 'invalid-guid' );
		}

		return $this->guidParser->parse( $guid )->getEntityId()->getSerialization();
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		return [
			'entity' => [
				ParamValidator::PARAM_TYPE => 'string',
			],
			'property' => [
				ParamValidator::PARAM_TYPE => 'string',
			],
			'claim' => [
				ParamValidator::PARAM_TYPE => 'string',
			],
			'rank' => [
				ParamValidator::PARAM_TYPE => StatementRankSerializer::getRanks(),
			],
			'props' => [
				ParamValidator::PARAM_TYPE => [
					'references',
				],
				ParamValidator::PARAM_DEFAULT => 'references',
				ParamValidator::PARAM_ISMULTI => true,
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
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
