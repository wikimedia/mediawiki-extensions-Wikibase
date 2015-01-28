<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use Wikibase\ChangeOp\ClaimChangeOpFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for setting the DataValue contained by the main snak of a claim.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SetClaimValue extends ModifyClaim {

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
	 * @see \ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		$claimGuid = $params['claim'];
		$entityId = $this->claimGuidParser->parse( $claimGuid )->getEntityId();
		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$entityRevision = $this->loadEntityRevision( $entityId, $baseRevisionId );
		$entity = $entityRevision->getEntity();

		$claim = $this->claimModificationHelper->getClaimFromEntity( $claimGuid, $entity );

		$snak = $this->claimModificationHelper->getSnakInstance( $params, $claim->getMainSnak()->getPropertyId() );

		$summary = $this->claimModificationHelper->createSummary( $params, $this );

		$changeOp = $this->claimChangeOpFactory->newSetMainSnakOp(
			$claimGuid,
			$snak
		);

		$this->claimModificationHelper->applyChangeOp( $changeOp, $entity, $summary );

		$this->saveChanges( $entity, $summary );
		$this->getResultBuilder()->markSuccess();
		$this->getResultBuilder()->addClaim( $claim );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * @since 0.4
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		if ( !( $this->claimModificationHelper->validateClaimGuid( $params['claim'] ) ) ) {
			$this->dieError( 'Invalid claim guid' , 'invalid-guid' );
		}
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			array(
				'claim' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_REQUIRED => true,
				),
				'value' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_REQUIRED => false,
				),
				'snaktype' => array(
					ApiBase::PARAM_TYPE => array( 'value', 'novalue', 'somevalue' ),
					ApiBase::PARAM_REQUIRED => true,
				),
			),
			parent::getAllowedParams()
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 *
	 * @return array
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wbsetclaimvalue&claim=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F&snaktype=value&value={"entity-type":"item","numeric-id":1}&token=foobar&baserevid=7201010' => 'apihelp-wbsetclaimvalue-example-1',
		);
	}
}
