<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Deserializers\Deserializer;
use Exception;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\StatementChangeOpFactory;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;

/**
 * Constructs ChangeOps for statement change requests (referred to as "claims" for legacy reasons).
 *
 * @license GPL-2.0+
 */
class ClaimsChangeOpDeserializer implements ChangeOpDeserializer {

	/**
	 * @var Deserializer
	 */
	private $statementDeserializer;

	/**
	 * @var StatementChangeOpFactory
	 */
	private $statementChangeOpFactory;

	public function __construct(
		Deserializer $statementDeserializer,
		StatementChangeOpFactory $statementChangeOpFactory
	) {
		$this->statementDeserializer = $statementDeserializer;
		$this->statementChangeOpFactory = $statementChangeOpFactory;
	}

	/**
	 * @see ChangeOpDeserializer::createEntityChangeOp
	 *
	 * @param array[] $changeRequest
	 *
	 * @return ChangeOp
	 * @throws ChangeOpDeserializationException
	 */
	public function createEntityChangeOp( array $changeRequest ) {
		$this->assertIsArray( $changeRequest['claims'] );

		$changeOps = [];

		//check if the array is associative or in arrays by property
		if ( array_keys( $changeRequest['claims'] ) !== range( 0, count( $changeRequest['claims'] ) - 1 ) ) {
			foreach ( $changeRequest['claims'] as $subClaims ) {
				$this->assertIsArray( $subClaims );

				$changeOps = array_merge(
					$changeOps,
					$this->getRemoveStatementChangeOps( $subClaims ),
					$this->getModifyStatementChangeOps( $subClaims )
				);
			}
		} else {
			$changeOps = array_merge(
				$changeOps,
				$this->getRemoveStatementChangeOps( $changeRequest['claims'] ),
				$this->getModifyStatementChangeOps( $changeRequest['claims'] )
			);
		}

		if ( count( $changeOps ) === 1 ) {
			return reset( $changeOps );
		}

		return new ChangeOps( $changeOps );
	}

	/**
	 * @param array[] $statements array of serialized statements
	 *
	 * @return ChangeOp[]
	 * @throws ChangeOpDeserializationException
	 */
	private function getModifyStatementChangeOps( array $statements ) {
		$opsToReturn = [];

		foreach ( $statements as $statementArray ) {
			if ( !array_key_exists( 'remove', $statementArray ) ) {
				try {
					$statement = $this->statementDeserializer->deserialize( $statementArray );

					if ( !( $statement instanceof Statement ) ) {
						throw new Exception( 'Statement serialization did not contain a Statement.' );
					}

					$opsToReturn[] = $this->statementChangeOpFactory->newSetStatementOp( $statement );
				} catch ( Exception $ex ) {
					$this->throwException( $ex->getMessage(), 'invalid-claim' );
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
		$opsToReturn = [];

		foreach ( $statements as $statementArray ) {
			if ( array_key_exists( 'remove', $statementArray ) ) {
				if ( array_key_exists( 'id', $statementArray ) ) {
					$opsToReturn[] = $this->statementChangeOpFactory->newRemoveStatementOp( $statementArray['id'] );
				} else {
					$this->throwException( 'Cannot remove a claim with no GUID', 'invalid-claim' );
				}
			}
		}

		return $opsToReturn;
	}

	/**
	 * @param array $claims
	 *
	 * @throws ChangeOpDeserializationException
	 */
	private function assertIsArray( $claims ) {
		if ( !is_array( $claims ) ) {
			$this->throwException( 'List of claims must be an array', 'not-recognized-array' );
		}
	}

	/**
	 * @param string $message
	 * @param string $errorCode
	 *
	 * @throws ChangeOpDeserializationException
	 */
	private function throwException( $message, $errorCode ) {
		throw new ChangeOpDeserializationException( $message, $errorCode );
	}

}
