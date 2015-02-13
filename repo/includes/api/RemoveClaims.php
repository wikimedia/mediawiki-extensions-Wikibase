<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\ClaimChangeOpFactory;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for removing claims.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class RemoveClaims extends ModifyClaim {

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
		$entityId = $this->getEntityId( $params );
		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$entityRevision = $this->loadEntityRevision( $entityId, $baseRevisionId );
		$entity = $entityRevision->getEntity();

		$this->checkClaims( $entity, $params['claim'] );
		$summary = $this->claimModificationHelper->createSummary( $params, $this );

		$changeOps = new ChangeOps();
		$changeOps->add( $this->getChangeOps( $params ) );

		try {
			$changeOps->apply( $entity, $summary );
		} catch ( ChangeOpException $e ) {
			$this->dieException( $e, 'failed-save' );
		}

		$this->saveChanges( $entity, $summary );
		$this->getResultBuilder()->markSuccess();
		$this->getResultBuilder()->setList( null, 'claims', $params['claim'], 'claim' );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Validates the parameters and returns the EntityId to act upon on success
	 *
	 * @param array $params
	 *
	 * @return EntityId
	 */
	private function getEntityId( array $params ) {
		$entityId = null;

		foreach ( $params['claim'] as $guid ) {
			if ( !$this->claimModificationHelper->validateClaimGuid( $guid ) ) {
				$this->dieError( "Invalid claim guid $guid" , 'invalid-guid' );
			}

			if ( is_null( $entityId ) ) {
				$entityId = $this->claimGuidParser->parse( $guid )->getEntityId();
			} else {
				if ( !$this->claimGuidParser->parse( $guid )->getEntityId()->equals( $entityId ) ) {
					$this->dieError( 'All claims must belong to the same entity' , 'invalid-guid' );
				}
			}
		}

		if ( is_null( $entityId ) ) {
			$this->dieError( 'Could not find an entity for the claims' , 'invalid-guid' );
		}

		return $entityId ;
	}

	/**
	 * Checks whether the claims can be found
	 *
	 * @param Entity $entity
	 * @param array $guids
	 */
	private function checkClaims( Entity $entity, array $guids ) {
		$claims = new Claims( $entity->getClaims() );

		foreach ( $guids as $guid) {
			if ( !$claims->hasClaimWithGuid( $guid ) ) {
				$this->dieError( "Claim with guid $guid not found" , 'invalid-guid' );
			}
		}
	}

	/**
	 * @param array $params
	 *
	 * @return ChangeOp[]
	 */
	private function getChangeOps( array $params ) {
		$changeOps = array();

		foreach ( $params['claim'] as $guid ) {
			$changeOps[] = $this->claimChangeOpFactory->newRemoveClaimOp( $guid );
		}

		return $changeOps;
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			array(
				'claim' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_ISMULTI => true,
					ApiBase::PARAM_REQUIRED => true,
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
			'action=wbremoveclaims&claim=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0N&token=foobar&baserevid=7201010' => 'apihelp-wbremoveclaims-example-1',
		);
	}

}
