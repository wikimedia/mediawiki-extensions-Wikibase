<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\DataAccess;

use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;
use Wikibase\Repo\Validators\SnakValidator;

/**
 * @license GPL-2.0-or-later
 */
class SnakValidatorStatementValidator implements StatementValidator {

	private StatementDeserializer $deserializer;
	private SnakValidator $snakValidator;

	private ?Statement $deserializedStatement = null;

	public function __construct( StatementDeserializer $deserializer, SnakValidator $snakValidator ) {
		$this->deserializer = $deserializer;
		$this->snakValidator = $snakValidator;
	}

	public function validate( array $statementSerialization ): ?ValidationError {
		$error = new ValidationError( self::CODE_INVALID );

		try {
			$deserializedStatement = $this->deserializer->deserialize( $statementSerialization );
		} catch ( \Exception $e ) {
			return $error;
		}

		if ( $this->snakValidator->validateStatementSnaks( $deserializedStatement )->isValid() ) {
			$this->deserializedStatement = $deserializedStatement;

			return null;
		}

		return $error;
	}

	public function getValidatedStatement(): ?Statement {
		return $this->deserializedStatement;
	}
}
