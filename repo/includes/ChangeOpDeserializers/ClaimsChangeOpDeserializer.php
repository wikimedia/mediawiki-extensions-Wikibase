<?php

namespace Wikibase\Repo\ChangeOpDeserializers;

use Deserializers\Deserializer;
use Exception;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\StatementChangeOpFactory;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikimedia\Assert\Assert;

/**
 * TODO: add class description
 *
 * @license GPL-2.0+
 */
class ClaimsChangeOpDeserializer implements ChangeOpDeserializer {

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var Deserializer
	 */
	private $statementDeserializer;

	/**
	 * @var StatementChangeOpFactory
	 */
	private $statementChangeOpFactory;

	public function __construct(
		ApiErrorReporter $errorReporter,
		Deserializer $statementDeserializer,
		StatementChangeOpFactory $statementChangeOpFactory
	) {
		$this->errorReporter = $errorReporter;
		$this->statementDeserializer = $statementDeserializer;
		$this->statementChangeOpFactory = $statementChangeOpFactory;
	}

	/**
	 * @see ChangeOpDeserializer::createEntityChangeOp
	 *
	 * @param array[] $changeRequest
	 *
	 * @return ChangeOp
	 */
	public function createEntityChangeOp( array $changeRequest ) {
		Assert::parameterType( 'array', $changeRequest['claims'], '$changeRequest[\'claims\']' );

		$changeOps = array();

		//check if the array is associative or in arrays by property
		if ( array_keys( $changeRequest['claims'] ) !== range( 0, count( $changeRequest['claims'] ) - 1 ) ) {
			foreach ( $changeRequest['claims'] as $subClaims ) {
				$changeOps = array_merge( $changeOps,
					$this->getRemoveStatementChangeOps( $subClaims ),
					$this->getModifyStatementChangeOps( $subClaims ) );
			}
		} else {
			$changeOps = array_merge( $changeOps,
				$this->getRemoveStatementChangeOps( $changeRequest['claims'] ),
				$this->getModifyStatementChangeOps( $changeRequest['claims'] ) );
		}

		return new ChangeOps( $changeOps );
	}

	/**
	 * @param array[] $statements array of serialized statements
	 *
	 * @return ChangeOp[]
	 */
	private function getModifyStatementChangeOps( array $statements ) {
		$opsToReturn = array();

		foreach ( $statements as $statementArray ) {
			if ( !array_key_exists( 'remove', $statementArray ) ) {
				try {
					$statement = $this->statementDeserializer->deserialize( $statementArray );

					if ( !( $statement instanceof Statement ) ) {
						throw new Exception( 'Statement serialization did not contained a Statement.' );
					}

					$opsToReturn[] = $this->statementChangeOpFactory->newSetStatementOp( $statement );
				} catch ( Exception $ex ) {
					$this->errorReporter->dieException( $ex, 'invalid-claim' );
				}
			}
		}
		return $opsToReturn;
	}

	/**
	 * Get changeops that remove all claims that have the 'remove' key in the array
	 *
	 * @param array[] $statements array of serialized claims
	 *
	 * @return ChangeOp[]
	 */
	private function getRemoveStatementChangeOps( array $statements ) {
		$opsToReturn = array();
		foreach ( $statements as $statementArray ) {
			if ( array_key_exists( 'remove', $statementArray ) ) {
				if ( array_key_exists( 'id', $statementArray ) ) {
					$opsToReturn[] = $this->statementChangeOpFactory->newRemoveStatementOp( $statementArray['id'] );
				} else {
					$this->errorReporter->dieError( 'Cannot remove a claim with no GUID', 'invalid-claim' );
				}
			}
		}
		return $opsToReturn;
	}

}
