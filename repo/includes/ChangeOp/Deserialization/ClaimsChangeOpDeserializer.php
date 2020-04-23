<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use Deserializers\Deserializer;
use Exception;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;

/**
 * Constructs ChangeOps for statement change requests (referred to as "claims" for legacy reasons).
 *
 * @license GPL-2.0-or-later
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

		//check if the array is associative or in arrays by property
		if ( array_keys( $changeRequest['claims'] ) !== range( 0, count( $changeRequest['claims'] ) - 1 ) ) {
			$changeOps = [];

			foreach ( $changeRequest['claims'] as $subClaims ) {
				$this->assertIsArray( $subClaims );

				$changeOps = array_merge(
					$changeOps,
					$this->createStatementChangeOps( $subClaims )
				);
			}
		} else {
			$changeOps = $this->createStatementChangeOps( $changeRequest['claims'] );
		}

		if ( count( $changeOps ) === 1 ) {
			return reset( $changeOps );
		}

		return new ChangeOps( $changeOps );
	}

	/**
	 * @param array[] $serializations Array of serialized statements.
	 *
	 * @return ChangeOp[]
	 * @throws ChangeOpDeserializationException
	 */
	private function createStatementChangeOps( array $serializations ) {
		$changeOps = [];

		foreach ( $serializations as $serialization ) {
			$changeOps[] = $this->createStatementChangeOp( $serialization );
		}

		return $changeOps;
	}

	/**
	 * @param array $serialization A serialized statement.
	 *
	 * @return ChangeOp
	 * @throws ChangeOpDeserializationException
	 */
	private function createStatementChangeOp( $serialization ) {
		if ( is_array( $serialization ) && array_key_exists( 'remove', $serialization ) ) {
			return $this->createRemoveStatementChangeOp( $serialization );
		}

		try {
			$statement = $this->statementDeserializer->deserialize( $serialization );

			if ( !( $statement instanceof Statement ) ) {
				throw new Exception( 'Statement serialization did not contain a Statement.' );
			}

			return $this->statementChangeOpFactory->newSetStatementOp( $statement );
		} catch ( Exception $ex ) {
			throw new ChangeOpDeserializationException( $ex->getMessage(), 'invalid-claim' );
		}
	}

	/**
	 * @param array $serialization A serialized statement.
	 *
	 * @return ChangeOp
	 * @throws ChangeOpDeserializationException
	 */
	private function createRemoveStatementChangeOp( array $serialization ) {
		if ( !array_key_exists( 'id', $serialization ) ) {
			throw new ChangeOpDeserializationException( 'Cannot remove a claim with no GUID',
				'invalid-claim' );
		}

		return $this->statementChangeOpFactory->newRemoveStatementOp( $serialization['id'] );
	}

	/**
	 * @param array $claims
	 *
	 * @throws ChangeOpDeserializationException
	 */
	private function assertIsArray( $claims ) {
		if ( !is_array( $claims ) ) {
			throw new ChangeOpDeserializationException( 'List of claims must be an array',
				'not-recognized-array' );
		}
	}

}
