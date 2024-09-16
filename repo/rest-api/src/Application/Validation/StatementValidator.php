<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use LogicException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldTypeException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\PropertyNotFoundException;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class StatementValidator {

	public const CODE_INVALID_FIELD = 'statement-validator-code-invalid-statement-field';
	public const CODE_MISSING_FIELD = 'statement-validator-code-missing-statement-field';
	public const CODE_INVALID_FIELD_TYPE = 'statement-validator-code-invalid-statement-type';
	public const CODE_PROPERTY_NOT_FOUND = 'statement-validator-code-property-not-found';

	public const CONTEXT_FIELD = 'statement-validator-context-field';
	public const CONTEXT_PATH = 'statement-validator-context-path';
	public const CONTEXT_VALUE = 'statement-validator-context-value';

	private StatementDeserializer $deserializer;

	private ?Statement $deserializedStatement = null;

	public function __construct( StatementDeserializer $deserializer ) {
		$this->deserializer = $deserializer;
	}

	public function validate( array $statementSerialization, string $basePath = '' ): ?ValidationError {
		try {
			$this->deserializedStatement = $this->deserializer->deserialize( $statementSerialization, $basePath );
		} catch ( MissingFieldException $e ) {
			return new ValidationError(
				self::CODE_MISSING_FIELD,
				[ self::CONTEXT_FIELD => $e->getField(), self::CONTEXT_PATH => $e->getPath() ]
			);
		} catch ( InvalidFieldException $e ) {
			return new ValidationError(
				self::CODE_INVALID_FIELD,
				[
					self::CONTEXT_FIELD => $e->getField(),
					self::CONTEXT_VALUE => $e->getValue(),
					self::CONTEXT_PATH => $e->getPath(),
				]
			);
		} catch ( InvalidFieldTypeException $e ) {
			return new ValidationError(
				self::CODE_INVALID_FIELD_TYPE,
				[
					self::CONTEXT_PATH => $e->getPath(),
					self::CONTEXT_VALUE => $e->getValue(),
				]
			);
		} catch ( PropertyNotFoundException $e ) {
			return new ValidationError(
				self::CODE_PROPERTY_NOT_FOUND,
				[
					self::CONTEXT_PATH => $e->getPath(),
					self::CONTEXT_VALUE => $e->getValue(),
				]
			);
		}

		return null;
	}

	public function getValidatedStatement(): Statement {
		if ( $this->deserializedStatement === null ) {
			throw new LogicException( 'getValidatedStatement() called before validate()' );
		}

		return $this->deserializedStatement;
	}

}
