<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Validation;

use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Serialization\MissingFieldException;
use Wikibase\Repo\RestApi\Serialization\StatementDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class StatementValidator {

	public const CODE_INVALID_FIELD = 'invalid-statement-field';
	public const CODE_MISSING_FIELD = 'statement-data-missing-field';

	public const CONTEXT_FIELD_NAME = 'field';
	public const CONTEXT_FIELD_VALUE = 'value';

	private StatementDeserializer $deserializer;

	private ?Statement $deserializedStatement = null;

	public function __construct( StatementDeserializer $deserializer ) {
		$this->deserializer = $deserializer;
	}

	public function validate( array $statementSerialization ): ?ValidationError {
		try {
			$this->deserializedStatement = $this->deserializer->deserialize( $statementSerialization );
		} catch ( MissingFieldException $e ) {
			return new ValidationError( self::CODE_MISSING_FIELD, [ self::CONTEXT_FIELD_NAME => $e->getField() ] );
		} catch ( InvalidFieldException $e ) {
			return new ValidationError(
				self::CODE_INVALID_FIELD,
				[
					self::CONTEXT_FIELD_NAME => $e->getField(),
					self::CONTEXT_FIELD_VALUE => $e->getValue(),
				]
			);
		}

		return null;
	}

	public function getValidatedStatement(): ?Statement {
		return $this->deserializedStatement;
	}

}
