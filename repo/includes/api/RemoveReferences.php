<?php

namespace Wikibase\Repo\Api;

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
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $this->getContext() );
		$changeOpFactoryProvider = WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider();

		$this->errorReporter = $apiHelperFactory->getErrorReporter( $this );
		$this->statementChangeOpFactory = $changeOpFactoryProvider->getStatementChangeOpFactory();
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		$guid = $params['statement'];
		$entityId = $this->guidParser->parse( $guid )->getEntityId();
		if ( isset( $params['baserevid'] ) ) {
			$entityRevision = $this->loadEntityRevision( $entityId, (int)$params['baserevid'] );
		} else {
			$entityRevision = $this->loadEntityRevision( $entityId );
		}
		$entity = $entityRevision->getEntity();
		$summary = $this->modificationHelper->createSummary( $params, $this );

		$claim = $this->modificationHelper->getStatementFromEntity( $guid, $entity );

		if ( ! ( $claim instanceof Statement ) ) {
			$this->errorReporter->dieError(
				'The referenced claim is not a statement and thus cannot have references',
				'not-statement'
			);
		}

		$referenceHashes = $this->getReferenceHashesFromParams( $params, $claim );

		$changeOps = new ChangeOps();
		$changeOps->add( $this->getChangeOps( $guid, $referenceHashes ) );

		try {
			$changeOps->apply( $entity, $summary );
		} catch ( ChangeOpException $e ) {
			$this->errorReporter->dieException( $e, 'failed-save' );
		}

		$status = $this->saveChanges( $entity, $summary );
		$this->getResultBuilder()->addRevisionIdFromStatusToResult( $status, 'pageinfo' );
		$this->getResultBuilder()->markSuccess();
	}

	/**
	 * Check the provided parameters
	 */
	private function validateParameters( array $params ) {
		if ( !( $this->modificationHelper->validateStatementGuid( $params['statement'] ) ) ) {
			$this->errorReporter->dieError( 'Invalid claim guid', 'invalid-guid' );
		}
	}

	/**
	 * @param string $guid
	 * @param string[] $referenceHashes
	 *
	 * @return ChangeOp[]
	 */
	private function getChangeOps( $guid, array $referenceHashes ) {
		$changeOps = array();

		foreach ( $referenceHashes as $referenceHash ) {
			$changeOps[] = $this->statementChangeOpFactory->newRemoveReferenceOp( $guid, $referenceHash );
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
				$this->errorReporter->dieError( 'Invalid reference hash', 'no-such-reference' );
			}
			$hashes[] = $referenceHash;
		}

		return $hashes;
	}

	/**
	 * @see ApiBase::isWriteMode
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @see ApiBase::needsToken
	 *
	 * @return string
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			array(
				'statement' => array(
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => true,
				),
				'references' => array(
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => true,
					self::PARAM_ISMULTI => true,
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
			'action=wbremovereferences&statement=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F'
				. '&references=455481eeac76e6a8af71a6b493c073d54788e7e9&token=foobar'
				. '&baserevid=7201010'
				=> 'apihelp-wbremovereferences-example-1',
		);
	}

}
