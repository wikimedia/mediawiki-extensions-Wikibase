<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use DataValues\IllegalValueException;
use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use FormatJson;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use UsageException;
use Wikibase\ChangeOp\ClaimChangeOpFactory;
use Wikibase\ClaimSummaryBuilder;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Lib\Serializers\SerializerFactory;
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
	 * @var ClaimChangeOpFactory
	 */
	protected $claimChangeOpFactory;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$changeOpFactoryProvider = WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider();
		$this->claimChangeOpFactory = $changeOpFactoryProvider->getClaimChangeOpFactory();
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

		if ( $guid === null ){
			$this->dieError( 'GUID must be set when setting a claim', 'invalid-claim' );
		}

		$claimGuid = $this->claimGuidParser->parse( $guid );

		$entityId = $claimGuid->getEntityId();
		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$entityRevision = $this->loadEntityRevision( $entityId, $baseRevisionId );
		$entity = $entityRevision->getEntity();

		$summary = $this->getSummary( $params, $claim, $entity );

		$changeop = $this->claimChangeOpFactory->newSetClaimOp(
			$claim,
			isset( $params['index'] ) ? $params['index'] : null
		);

		$this->claimModificationHelper->applyChangeOp( $changeop, $entity, $summary );

		$this->saveChanges( $entity, $summary );
		$this->getResultBuilder()->markSuccess();
		$this->getResultBuilder()->addClaim( $claim );
	}

	/**
	 * @param array $params
	 * @param Claim $claim
	 * @param Entity $entity
	 *
	 * @return Summary
	 *
	 * @todo this summary builder is ugly and summary stuff needs to be refactored
	 */
	protected function getSummary( array $params, Claim $claim, Entity $entity ){
		$claimSummaryBuilder = new ClaimSummaryBuilder(
			$this->getModuleName(),
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) )
		);
		$summary = $claimSummaryBuilder->buildClaimSummary(
			new Claims( $entity->getClaims() ),
			$claim
		);

		if ( isset( $params['summary'] ) ) {
			$summary->setUserSummary( $params['summary'] );
		}

		return $summary;
	}

	/**
	 * @since 0.4
	 *
	 * @param array $params
	 *
	 * @throws IllegalValueException
	 * @throws UsageException
	 * @throws LogicException
	 * @return Claim
	 */
	protected function getClaimFromParams( array $params ) {
		$serializerFactory = new SerializerFactory();
		$unserializer = $serializerFactory->newUnserializerForClass( 'Wikibase\DataModel\Claim\Claim' );

		try {
			$serializedClaim = FormatJson::decode( $params['claim'], true );
			if ( !is_array( $serializedClaim ) ){
				throw new IllegalValueException( 'Failed to get claim from claim Serialization' );
			}
			$claim = $unserializer->newFromSerialization( $serializedClaim );
			if ( !$claim instanceof Claim ) {
				throw new IllegalValueException( 'Failed to get claim from claim Serialization' );
			}
			return $claim;
		} catch ( InvalidArgumentException $invalidArgumentException ) {
			$this->dieError( 'Failed to get claim from claim Serialization ' . $invalidArgumentException->getMessage(), 'invalid-claim' );
		} catch ( OutOfBoundsException $outOfBoundsException ) {
			$this->dieError( 'Failed to get claim from claim Serialization ' . $outOfBoundsException->getMessage(), 'invalid-claim' );
		}

		// Note: since dieUsage() never returns, this should be unreachable!
		throw new LogicException( 'ApiBase::dieUsage did not throw a UsageException' );
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			array(
				'claim' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_REQUIRED => true
				),
				'index' => array(
					ApiBase::PARAM_TYPE => 'integer',
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
			'action=wbsetclaim&claim={"id":"Q2$5627445f-43cb-ed6d-3adb-760e85bd17ee","type":"claim","mainsnak":{"snaktype":"value","property":"P1","datavalue":{"value":"City","type":"string"}}}'
			=> 'apihelp-wbsetclaim-example-1',
			'action=wbsetclaim&claim={"id":"Q2$5627445f-43cb-ed6d-3adb-760e85bd17ee","type":"claim","mainsnak":{"snaktype":"value","property":"P1","datavalue":{"value":"City","type":"string"}}}&index=0'
			=> 'apihelp-wbsetclaim-example-2',
		);
	}

}
