<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\StatementChangeOpFactory;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for removing one or more references of the same statement.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class RemoveReferences extends ModifyClaim {

	/**
	 * @var StatementChangeOpFactory
	 */
	private $statementChangeOpFactory;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$changeOpFactoryProvider = WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider();
		$this->statementChangeOpFactory = $changeOpFactoryProvider->getStatementChangeOpFactory();
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		$claimGuid = $params['statement'];
		$entityId = $this->claimGuidParser->parse( $claimGuid )->getEntityId();
		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$entityRevision = $this->loadEntityRevision( $entityId, $baseRevisionId );
		$entity = $entityRevision->getEntity();
		$summary = $this->claimModificationHelper->createSummary( $params, $this );

		$claim = $this->claimModificationHelper->getClaimFromEntity( $claimGuid, $entity );

		if ( ! ( $claim instanceof Statement ) ) {
			$this->dieError( 'The referenced claim is not a statement and thus cannot have references', 'not-statement' );
		}

		$referenceHashes = $this->getReferenceHashesFromParams( $params, $claim );

		$changeOps = new ChangeOps();
		$changeOps->add( $this->getChangeOps( $claimGuid, $referenceHashes ) );

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
		if ( !( $this->claimModificationHelper->validateClaimGuid( $params['statement'] ) ) ) {
			$this->dieError( 'Invalid claim guid' , 'invalid-guid' );
		}
	}

	/**
	 * @param string $claimGuid
	 * @param string[] $referenceHashes
	 *
	 * @return ChangeOp[]
	 */
	private function getChangeOps( $claimGuid, array $referenceHashes ) {
		$changeOps = array();

		foreach ( $referenceHashes as $referenceHash ) {
			$changeOps[] = $this->statementChangeOpFactory->newRemoveReferenceOp( $claimGuid, $referenceHash );
		}

		return $changeOps;
	}

	/**
	 * @param array $params
	 * @param Statement $statement
	 *
	 * @return string[]
	 */
	private function getReferenceHashesFromParams( array $params, Statement $statement ) {
		$references = $statement->getReferences();
		$hashes = array();

		foreach ( array_unique( $params['references'] ) as $referenceHash ) {
			if ( !$references->hasReferenceHash( $referenceHash ) ) {
				$this->dieError( 'Invalid reference hash', 'no-such-reference' );
			}
			$hashes[] = $referenceHash;
		}

		return $hashes;
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			array(
				'statement' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_REQUIRED => true,
				),
				'references' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_REQUIRED => true,
					ApiBase::PARAM_ISMULTI => true,
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
			'action=wbremovereferences&statement=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F&references=455481eeac76e6a8af71a6b493c073d54788e7e9&token=foobar&baserevid=7201010' => 'apihelp-wbremovereferences-example-1',
		);
	}

}
