<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Exception;
use InvalidArgumentException;
use Swaggest\JsonDiff\JsonPatch;
use Swaggest\JsonDiff\PatchTestOperationFailedException;
use Throwable;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Domain\Exceptions\InapplicablePatchException;
use Wikibase\Repo\RestApi\Domain\Exceptions\InvalidPatchedSerializationException;
use Wikibase\Repo\RestApi\Domain\Exceptions\InvalidPatchedStatementException;
use Wikibase\Repo\RestApi\Domain\Exceptions\PatchTestConditionFailedException;
use Wikibase\Repo\RestApi\Domain\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Domain\Services\StatementPatcher;
use Wikibase\Repo\Validators\SnakValidator;

/**
 * @license GPL-2.0-or-later
 */
class JsonDiffStatementPatcher implements StatementPatcher {

	private StatementSerializer $serializer;
	private StatementDeserializer $deserializer;
	private SnakValidator $snakValidator;

	public function __construct(
		StatementSerializer $serializer,
		StatementDeserializer $deserializer,
		SnakValidator $snakValidator
	) {
		$this->serializer = $serializer;
		$this->deserializer = $deserializer;
		$this->snakValidator = $snakValidator;
	}

	/**
	 * @inheritDoc
	 */
	public function patch( Statement $statement, array $patch ): Statement {
		try {
			$patchDocument = JsonPatch::import( $patch );
		} catch ( Throwable $e ) {
			throw new InvalidArgumentException( 'Invalid patch' );
		}

		$statementSerialization = $this->serializer->serialize( $statement );

		$patchDocument->setFlags( JsonPatch::TOLERATE_ASSOCIATIVE_ARRAYS );

		try {
			$patchDocument->apply( $statementSerialization );
		} catch ( PatchTestOperationFailedException $e ) {
			throw new PatchTestConditionFailedException();
		} catch ( Exception $e ) {
			throw new InapplicablePatchException();
		}

		try {
			$patchedStatement = $this->deserializer->deserialize( $statementSerialization );
		} catch ( Exception $e ) {
			throw new InvalidPatchedSerializationException( $e->getMessage() );
		}

		if ( !$this->snakValidator->validateStatementSnaks( $patchedStatement )->isValid() ) {
			throw new InvalidPatchedStatementException();
		}

		return $patchedStatement;
	}
}
