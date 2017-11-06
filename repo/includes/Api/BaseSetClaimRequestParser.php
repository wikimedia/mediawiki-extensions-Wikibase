<?php

namespace Wikibase\Repo\Api;

use DataValues\IllegalValueException;
use Wikibase\DataModel\Deserializers\StatementDeserializer;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParsingException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;

/**
 * TEST
 */
class BaseSetClaimRequestParser implements SetClaimRequestParser {

	// TODO: do not pass/use it here. Make Parser return some kind of "result" object containing
	// either Request or Errors, and have API class report Errors appropriately.
	// Used here only as a shortcut when creating proof-of-concept implementation
	private $errorReporter;

	private $statementDeserializer;

	private $changeOpFactory;

	private $guidParser;

	public function __construct(
		ApiErrorReporter $errorReporter,
		StatementDeserializer $statementDeserializer,
		StatementChangeOpFactory $statementChangeOpFactory,
		StatementGuidParser $guidParser
	) {
		$this->errorReporter = $errorReporter;
		$this->statementDeserializer = $statementDeserializer;
		$this->changeOpFactory = $statementChangeOpFactory;
		$this->guidParser = $guidParser;
	}

	public function parse( array $params ) {
		$statement = $this->getStatementFromParams( $params );
		$guid = $statement->getGuid();

		if ( $guid === null ) {
			$this->errorReporter->dieError( 'GUID must be set when setting a claim', 'invalid-claim' );
		}

		try {
			$statementGuid = $this->guidParser->parse( $guid );
		} catch ( StatementGuidParsingException $ex ) {
			$this->errorReporter->dieException( $ex, 'invalid-claim' );
			throw new \LogicException( 'ApiErrorReporter::dieError did not throw an exception' );
		}

		$entityId = $statementGuid->getEntityId();

		$index = isset( $params['index'] ) ? $params['index'] : null;

		// TODO: should this be parser's responsibility?
		$changeOp = $this->changeOpFactory->newSetStatementOp( $statement, $index );

		return new SetClaimRequest( $entityId, $statement, $changeOp );
	}

	/**
	 * @param array $params
	 *
	 * @throws IllegalValueException
	 * @throws \ApiUsageException
	 * @throws \LogicException
	 * @return Statement
	 */
	private function getStatementFromParams( array $params ) {
		try {
			$serializedStatement = json_decode( $params['claim'], true );
			if ( !is_array( $serializedStatement ) ) {
				throw new IllegalValueException( 'Failed to get statement from Serialization' );
			}
			$statement = $this->statementDeserializer->deserialize( $serializedStatement );
			if ( !( $statement instanceof Statement ) ) {
				throw new IllegalValueException( 'Failed to get statement from Serialization' );
			}
			return $statement;
		} catch ( \InvalidArgumentException $invalidArgumentException ) {
			$this->errorReporter->dieError(
				'Failed to get claim from claim Serialization ' . $invalidArgumentException->getMessage(),
				'invalid-claim'
			);
		} catch ( \OutOfBoundsException $outOfBoundsException ) {
			$this->errorReporter->dieError(
				'Failed to get claim from claim Serialization ' . $outOfBoundsException->getMessage(),
				'invalid-claim'
			);
		}

		// Note: since dieUsage() never returns, this should be unreachable!
		throw new \LogicException( 'ApiErrorReporter::dieError did not throw an exception' );
	}

}
