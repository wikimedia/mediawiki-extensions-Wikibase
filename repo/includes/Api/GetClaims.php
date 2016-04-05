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
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StatementRankSerializer;

/**
 * API module for getting claims.
 *
 * @since 0.3
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
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		//TODO: provide a mechanism to override the services
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $this->getContext() );
		$this->errorReporter = $apiHelperFactory->getErrorReporter( $this );
		$this->resultBuilder = $apiHelperFactory->getResultBuilder( $this );
		$this->entityLoadingHelper = $apiHelperFactory->getEntityLoadingHelper( $this );
		$this->guidValidator = $wikibaseRepo->getStatementGuidValidator();
		$this->guidParser = $wikibaseRepo->getStatementGuidParser();
		$this->idParser = $wikibaseRepo->getEntityIdParser();
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		list( $idString, $guid ) = $this->getIdentifiers( $params );

		try {
			$entityId = $this->idParser->parse( $idString );
		} catch ( EntityIdParsingException $e ) {
			$this->errorReporter->dieException( $e, 'param-invalid' );
		}

		/** @var EntityId $entityId */
		$entityRevision = $this->entityLoadingHelper->loadEntityRevision(
			$entityId,
			EntityRevisionLookup::LATEST_FROM_SLAVE
		);
		$entity = $entityRevision->getEntity();

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

		return array( $idString, $guid );
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
		return array(
			'entity' => array(
				self::PARAM_TYPE => 'string',
			),
			'property' => array(
				self::PARAM_TYPE => 'string',
			),
			'claim' => array(
				self::PARAM_TYPE => 'string',
			),
			'rank' => array(
				self::PARAM_TYPE => StatementRankSerializer::getRanks(),
			),
			'props' => array(
				self::PARAM_TYPE => array(
					'references',
				),
				self::PARAM_DFLT => 'references',
				self::PARAM_ISMULTI => true,
			),
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			"action=wbgetclaims&entity=Q42" =>
				"apihelp-wbgetclaims-example-1",
			"action=wbgetclaims&entity=Q42&property=P2" =>
				"apihelp-wbgetclaims-example-2",
			"action=wbgetclaims&entity=Q42&rank=normal" =>
				"apihelp-wbgetclaims-example-3",
			'action=wbgetclaims&claim=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F' =>
				'apihelp-wbgetclaims-example-4',
		);
	}

}
