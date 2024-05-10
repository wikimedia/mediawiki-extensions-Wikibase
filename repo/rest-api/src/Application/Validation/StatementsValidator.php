<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use LogicException;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldTypeException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidStatementsException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\PropertyIdMismatchException;
use Wikibase\Repo\RestApi\Application\Serialization\StatementsDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class StatementsValidator {

	public const CODE_STATEMENTS_NOT_ASSOCIATIVE = 'invalid-statements-type';
	public const CODE_STATEMENT_GROUP_NOT_SEQUENTIAL = 'invalid-statement-group-type';
	public const CODE_STATEMENT_NOT_ARRAY = 'invalid-statement-type';
	public const CODE_INVALID_STATEMENT_DATA = 'statement-data-invalid-field';
	public const CODE_MISSING_STATEMENT_DATA = 'statement-data-missing-field';
	public const CODE_PROPERTY_ID_MISMATCH = 'property-id-mismatch';

	public const CONTEXT_STATEMENTS = 'statements';
	public const CONTEXT_PATH = 'path';
	public const CONTEXT_FIELD = 'field';
	public const CONTEXT_VALUE = 'value';
	public const CONTEXT_PROPERTY_ID_KEY = 'property-id-key';
	public const CONTEXT_PROPERTY_ID_VALUE = 'property-id-value';

	private StatementsDeserializer $statementsDeserializer;

	private ?StatementList $deserializedStatements = null;

	public function __construct( StatementsDeserializer $statementsDeserializer ) {
		$this->statementsDeserializer = $statementsDeserializer;
	}

	public function validate( array $statements ): ?ValidationError {
		try {
			$this->deserializedStatements = $this->statementsDeserializer->deserialize( $statements );
		} catch ( InvalidFieldTypeException $e ) {
			switch ( substr_count( $e->getField(), '/', ) ) {
				case 0:
					return new ValidationError(
						self::CODE_STATEMENT_GROUP_NOT_SEQUENTIAL,
						[ self::CONTEXT_PATH => $e->getField() ]
					);
				case 1:
					return new ValidationError(
						self::CODE_STATEMENT_NOT_ARRAY,
						[ self::CONTEXT_PATH => $e->getField() ]
					);
				default:
					throw new LogicException( 'Unable to handle exception' );
			}
		} catch ( InvalidStatementsException $e ) {
			return new ValidationError(
				self::CODE_STATEMENTS_NOT_ASSOCIATIVE,
				[ self::CONTEXT_PATH => $e->getField(), self::CONTEXT_STATEMENTS => $e->getValue() ]
			);
		} catch ( InvalidFieldException $e ) {
			return new ValidationError(
				self::CODE_INVALID_STATEMENT_DATA,
				[
					self::CONTEXT_PATH => $e->getPath(),
					self::CONTEXT_FIELD => $e->getField(),
					self::CONTEXT_VALUE => $e->getValue(),
				]
			);
		} catch ( MissingFieldException $e ) {
			return new ValidationError(
				self::CODE_MISSING_STATEMENT_DATA,
				[
					self::CONTEXT_PATH => $e->getPath(),
					self::CONTEXT_FIELD => $e->getField(),
				]
			);
		} catch ( PropertyIdMismatchException $e ) {
			return new ValidationError(
				self::CODE_PROPERTY_ID_MISMATCH,
				[
					self::CONTEXT_PATH => $e->getPath(),
					self::CONTEXT_PROPERTY_ID_KEY => $e->getPropertyIdKey(),
					self::CONTEXT_PROPERTY_ID_VALUE => $e->getPropertyIdValue(),
				]
			);
		}

		return null;
	}

	public function getValidatedStatements(): StatementList {
		if ( $this->deserializedStatements === null ) {
			throw new LogicException( 'getValidatedStatements() called before validate()' );
		}

		return $this->deserializedStatements;
	}

}
