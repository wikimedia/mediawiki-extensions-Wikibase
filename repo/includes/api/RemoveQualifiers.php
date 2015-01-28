<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\ClaimChangeOpFactory;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for removing qualifiers from a claim.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class RemoveQualifiers extends ModifyClaim {

	/**
	 * @var ClaimChangeOpFactory
	 */
	private $claimChangeOpFactory;

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
		$summary = $this->claimModificationHelper->createSummary( $params, $this );

		$claim = $this->claimModificationHelper->getClaimFromEntity( $claimGuid, $entity );

		$qualifierHashes = $this->getQualifierHashesFromParams( $params, $claim );

		$changeOps = new ChangeOps();
		$changeOps->add( $this->getChangeOps( $claimGuid, $qualifierHashes ) );

		try {
			$changeOps->apply( $entity, $summary );
		} catch ( ChangeOpException $e ) {
			$this->dieException( $e, 'failed-save' );
		}

		$this->saveChanges( $entity, $summary );
		$this->getResultBuilder()->markSuccess();

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Check the provided parameters
	 */
	private function validateParameters( array $params ) {
		if ( !( $this->claimModificationHelper->validateClaimGuid( $params['claim'] ) ) ) {
			$this->dieError( 'Invalid claim guid' , 'invalid-guid' );
		}
	}

	/**
	 * @param string $claimGuid
	 * @param string[] $qualifierHashes
	 *
	 * @return ChangeOp[]
	 */
	private function getChangeOps( $claimGuid, array $qualifierHashes ) {
		$changeOps = array();

		foreach ( $qualifierHashes as $qualifierHash ) {
			$changeOps[] = $this->claimChangeOpFactory->newRemoveQualifierOp( $claimGuid, $qualifierHash );
		}

		return $changeOps;
	}

	/**
	 * @param array $params
	 * @param Claim $claim
	 *
	 * @return string[]
	 */
	private function getQualifierHashesFromParams( array $params, Claim $claim ) {
		$qualifiers = $claim->getQualifiers();
		$hashes = array();

		foreach ( array_unique( $params['qualifiers'] ) as $qualifierHash ) {
			if ( !$qualifiers->hasSnakHash( $qualifierHash ) ) {
				$this->dieError( 'Invalid snak hash', 'no-such-qualifier' );
			}
			$hashes[] = $qualifierHash;
		}

		return $hashes;
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
				'qualifiers' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_REQUIRED => true,
					ApiBase::PARAM_ISMULTI => true,
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
			'action=wbremovequalifiers&statement=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F&references=1eb8793c002b1d9820c833d234a1b54c8e94187e&token=foobar&baserevid=7201010'=>
				'apihelp-wbremovequalifiers-example-1',
		);
	}

}
