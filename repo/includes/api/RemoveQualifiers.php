<?php

namespace Wikibase\Api;

use ApiBase;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpQualifierRemove;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\DataModel\Claim\Claim;

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
			$this->dieUsage( $e->getMessage(), 'failed-save' );
		}

		$this->saveChanges( $entity, $summary );
		$this->getResultBuilder()->markSuccess();

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Check the provided parameters
	 *
	 * @since 0.4
	 */
	protected function validateParameters( array $params ) {
		if ( !( $this->claimModificationHelper->validateClaimGuid( $params['claim'] ) ) ) {
			$this->dieUsage( 'Invalid claim guid' , 'invalid-guid' );
		}
	}

	/**
	 * @since 0.4
	 *
	 * @param string $claimGuid
	 * @param array $qualifierHashes
	 *
	 * @return ChangeOp[] $changeOps
	 */
	protected function getChangeOps( $claimGuid, array $qualifierHashes ) {
		$changeOps = array();

		foreach ( $qualifierHashes as $qualifierHash ) {
			$changeOps[] = new ChangeOpQualifierRemove( $claimGuid, $qualifierHash );
		}

		return $changeOps;
	}

	/**
	 * @since 0.4
	 *
	 * @param array $params
	 * @param Claim $claim
	 *
	 * @return string[]
	 */
	protected function getQualifierHashesFromParams( array $params, Claim $claim ) {
		$qualifiers = $claim->getQualifiers();
		$hashes = array();

		foreach ( array_unique( $params['qualifiers'] ) as $qualifierHash ) {
			if ( !$qualifiers->hasSnakHash( $qualifierHash ) ) {
				$this->dieUsage( 'Invalid snak hash', 'no-such-qualifier' );
			}
			$hashes[] = $qualifierHash;
		}

		return $hashes;
	}

	/**
	 * @see \ApiBase::getAllowedParams
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getAllowedParams() {
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
	 * @see \ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge(
			parent::getPossibleErrors(),
			array(
				array( 'code' => 'no-such-qualifier', 'info' => $this->msg( 'wikibase-api-no-such-qualifer' )->text() ),
			)
		);
	}

	/**
	 * @see \ApiBase::getParamDescription
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array_merge(
			parent::getParamDescription(),
			array(
				'claim' => 'A GUID identifying the claim from which to remove qualifiers',
				'qualifiers' => 'Snak hashes of the qualifiers to remove',
			)
		);
	}

	/**
	 * @see \ApiBase::getDescription
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getDescription() {
		return array(
			'API module for removing a qualifier from a claim.'
		);
	}

	/**
	 * @see \ApiBase::getExamples
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbremovequalifiers&statement=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F&references=1eb8793c002b1d9820c833d234a1b54c8e94187e&token=foobar&baserevid=7201010'=>
				'Remove qualifier with hash "1eb8793c002b1d9820c833d234a1b54c8e94187e" from claim with GUID of "Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F"',
		);
	}
}
