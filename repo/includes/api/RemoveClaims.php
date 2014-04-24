<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ClaimChangeOpFactory;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\ChangeOpException;
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
			$this->dieUsage( $e->getMessage(), 'failed-save' );
		}

		$this->saveChanges( $entity, $summary );
		$this->getResultBuilder()->markSuccess();
		$this->getResultBuilder()->setList( null, 'claims', $params['claim'], 'claim' );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Validates the parameters and returns the EntityId to act upon on success
	 *
	 * @since 0.4
	 *
	 * @param array $params
	 *
	 * @return EntityId
	 */
	protected function getEntityId( array $params ) {
		$entityId = null;

		foreach ( $params['claim'] as $guid ) {
			if ( !$this->claimModificationHelper->validateClaimGuid( $guid ) ) {
				$this->dieUsage( "Invalid claim guid $guid" , 'invalid-guid' );
			}

			if ( is_null( $entityId ) ) {
				$entityId = $this->claimGuidParser->parse( $guid )->getEntityId();
			} else {
				if ( !$this->claimGuidParser->parse( $guid )->getEntityId()->equals( $entityId ) ) {
					$this->dieUsage( 'All claims must belong to the same entity' , 'invalid-guid' );
				}
			}
		}

		if ( is_null( $entityId ) ) {
			$this->dieUsage( 'Could not find an entity for the claims' , 'invalid-guid' );
		}

		return $entityId ;
	}

	/**
	 * Checks whether the claims can be found
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param array $guids
	 */
	protected function checkClaims( Entity $entity, array $guids ) {
		$claims = new Claims( $entity->getClaims() );

		foreach ( $guids as $guid) {
			if ( !$claims->hasClaimWithGuid( $guid ) ) {
				$this->dieUsage( "Claim with guid $guid not found" , 'invalid-guid' );
			}
		}
	}

	/**
	 * @since 0.4
	 *
	 * @param array $params
	 *
	 * @return ChangeOp[]
	 */
	protected function getChangeOps( array $params ) {
		$changeOps = array();

		foreach ( $params['claim'] as $guid ) {
			$changeOps[] = $this->claimChangeOpFactory->newRemoveClaimOp( $guid );
		}

		return $changeOps;
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
					ApiBase::PARAM_ISMULTI => true,
					ApiBase::PARAM_REQUIRED => true,
				),
			),
			parent::getAllowedParams()
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
				'claim' => array( 'One GUID or several (pipe-separated) GUIDs identifying the claims to be removed.',
					'All claims must belong to the same entity.'
				),
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
			'API module for removing Wikibase claims.'
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
			'api.php?action=wbremoveclaims&claim=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0N&token=foobar&baserevid=7201010' => 'Remove claim with GUID of "Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0N"',
		);
	}

}
