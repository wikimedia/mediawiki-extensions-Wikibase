<?php

namespace Wikibase\Repo\Api;

use ApiMain;
use DataValues\IllegalValueException;
use Deserializers\Deserializer;
use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use UsageException;
use Wikibase\ChangeOp\StatementChangeOpFactory;
use Wikibase\ClaimSummaryBuilder;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Services\Statement\StatementGuidParsingException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * API module for creating or updating an entire Claim.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Adam Shorland
 */
class SetClaim extends ModifyClaim {

	/**
	 * @var StatementChangeOpFactory
	 */
	private $statementChangeOpFactory;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var Deserializer
	 */
	private $statementDeserializer;

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

		$this->statementDeserializer = $wikibaseRepo->getStatementDeserializer();
		$this->errorReporter = $apiHelperFactory->getErrorReporter( $this );
		$this->statementChangeOpFactory = $changeOpFactoryProvider->getStatementChangeOpFactory();
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.4
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$claim = $this->getClaimFromParams( $params );
		$guid = $claim->getGuid();

		if ( $guid === null ) {
			$this->errorReporter->dieError( 'GUID must be set when setting a claim', 'invalid-claim' );
		}

		try {
			$claimGuid = $this->guidParser->parse( $guid );
		} catch ( StatementGuidParsingException $ex ) {
			$this->errorReporter->dieException( $ex, 'invalid-claim' );
		}

		$entityId = $claimGuid->getEntityId();
		if ( isset( $params['baserevid'] ) ) {
			$entityRevision = $this->loadEntityRevision( $entityId, (int)$params['baserevid'] );
		} else {
			$entityRevision = $this->loadEntityRevision( $entityId );
		}
		$entity = $entityRevision->getEntity();

		$summary = $this->getSummary( $params, $claim, $entity );

		$changeop = $this->statementChangeOpFactory->newSetStatementOp(
			$claim,
			isset( $params['index'] ) ? $params['index'] : null
		);

		$this->modificationHelper->applyChangeOp( $changeop, $entity, $summary );

		$status = $this->saveChanges( $entity, $summary );
		$this->getResultBuilder()->addRevisionIdFromStatusToResult( $status, 'pageinfo' );
		$this->getResultBuilder()->markSuccess();
		$this->getResultBuilder()->addStatement( $claim );
	}

	/**
	 * @param array $params
	 * @param Statement $statement
	 * @param Entity $entity
	 *
	 * @throws InvalidArgumentException
	 * @return Summary
	 *
	 * @todo this summary builder is ugly and summary stuff needs to be refactored
	 */
	private function getSummary( array $params, Statement $statement, Entity $entity ) {
		if ( !( $entity instanceof StatementListProvider ) ) {
			throw new InvalidArgumentException( '$entity must be a StatementListProvider' );
		}

		$claimSummaryBuilder = new ClaimSummaryBuilder(
			$this->getModuleName(),
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) )
		);

		$summary = $claimSummaryBuilder->buildClaimSummary(
			$entity->getStatements()->getFirstStatementWithGuid( $statement->getGuid() ),
			$statement
		);

		if ( isset( $params['summary'] ) ) {
			$summary->setUserSummary( $params['summary'] );
		}

		return $summary;
	}

	/**
	 * @param array $params
	 *
	 * @throws IllegalValueException
	 * @throws UsageException
	 * @throws LogicException
	 * @return Statement
	 */
	private function getClaimFromParams( array $params ) {

		try {
			$serializedStatement = json_decode( $params['claim'], true );
			if ( !is_array( $serializedStatement ) ) {
				throw new IllegalValueException( 'Failed to get statement from Serialization' );
			}
			$claim = $this->statementDeserializer->deserialize( $serializedStatement );
			if ( !$claim instanceof Statement ) {
				throw new IllegalValueException( 'Failed to get statement from Serialization' );
			}
			return $claim;
		} catch ( InvalidArgumentException $invalidArgumentException ) {
			$this->errorReporter->dieError(
				'Failed to get claim from claim Serialization ' . $invalidArgumentException->getMessage(),
				'invalid-claim'
			);
		} catch ( OutOfBoundsException $outOfBoundsException ) {
			$this->errorReporter->dieError(
				'Failed to get claim from claim Serialization ' . $outOfBoundsException->getMessage(),
				'invalid-claim'
			);
		}

		// Note: since dieUsage() never returns, this should be unreachable!
		throw new LogicException( 'ApiErrorReporter::dieError did not throw an exception' );
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			array(
				'claim' => array(
					self::PARAM_TYPE => 'text',
					self::PARAM_REQUIRED => true,
				),
				'index' => array(
					self::PARAM_TYPE => 'integer',
				),
			),
			parent::getAllowedParams()
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
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
		);
	}

}
