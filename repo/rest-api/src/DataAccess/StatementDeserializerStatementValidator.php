<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\DataAccess;

use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class StatementDeserializerStatementValidator implements StatementValidator {

	private StatementDeserializer $deserializer;

	private ?Statement $deserializedStatement = null;

	public function __construct( StatementDeserializer $deserializer ) {
		$this->deserializer = $deserializer;
	}

	public function validate( array $statementSerialization ): ?ValidationError {
		try {
			$this->deserializedStatement = $this->deserializer->deserialize( $statementSerialization );
		} catch ( \Exception $e ) {
			return new ValidationError( self::CODE_INVALID );
		}

		return null;
	}

	public function getValidatedStatement(): ?Statement {
		return $this->deserializedStatement;
	}

}
