<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use LogicException;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @license GPL-2.0-or-later
 */
class StatementsValidator {

	public const CODE_STATEMENTS_NOT_ASSOCIATIVE = 'statements-validator-code-invalid-statements-type';
	public const CODE_STATEMENT_GROUP_NOT_SEQUENTIAL = 'statements-validator-code-invalid-statement-group-type';
	public const CODE_STATEMENT_NOT_ARRAY = 'statements-validator-code-invalid-statement-type';
	public const CODE_PROPERTY_ID_MISMATCH = 'statements-validator-code-property-id-mismatch';

	public const CONTEXT_STATEMENTS = 'statements-validator-context-statements';
	public const CONTEXT_PATH = 'statements-validator-context-path';
	public const CONTEXT_FIELD = 'statements-validator-context-field';
	public const CONTEXT_VALUE = 'statements-validator-context-value';
	public const CONTEXT_PROPERTY_ID_KEY = 'statements-validator-context-property-id-key';
	public const CONTEXT_PROPERTY_ID_VALUE = 'statements-validator-context-property-id-value';

	private StatementValidator $statementValidator;

	private ?StatementList $deserializedStatements = null;

	public function __construct( StatementValidator $statementValidator ) {
		$this->statementValidator = $statementValidator;
	}

	public function validate( array $serialization, string $basePath = '' ): ?ValidationError {
		return $this->validateModifiedStatements( [], new StatementList(), $serialization, $basePath );
	}

	public function validateModifiedStatements(
		array $originalSerialization,
		StatementList $originalStatements,
		array $serialization,
		string $basePath = ''
	): ?ValidationError {
		if ( count( $serialization ) && array_is_list( $serialization ) ) {
			return new ValidationError( self::CODE_STATEMENTS_NOT_ASSOCIATIVE, [
				self::CONTEXT_PATH => $basePath, self::CONTEXT_STATEMENTS => $serialization,
			] );
		}

		$deserializedStatements = [];
		foreach ( $serialization as $propertyId => $statementGroup ) {
			// @phan-suppress-next-line PhanRedundantConditionInLoop - $statementGroup is not guaranteed to be an array
			if ( !is_array( $statementGroup ) || !array_is_list( $statementGroup ) ) {
				return new ValidationError( self::CODE_STATEMENT_GROUP_NOT_SEQUENTIAL, [
					self::CONTEXT_PATH => "$basePath/$propertyId",
				] );
			}
			foreach ( $statementGroup as $groupIndex => $statement ) {
				if ( isset( $statement['id'] ) && is_string( $statement['id'] ) &&
					$statement === $this->findInGroup( $originalSerialization[$propertyId] ?? [], $statement['id'] ) ) {
					$deserializedStatements[] = $originalStatements->getFirstStatementWithGuid( $statement['id'] );
					continue;
				}

				if ( !is_array( $statement ) ) {
					return new ValidationError( self::CODE_STATEMENT_NOT_ARRAY, [
						self::CONTEXT_PATH => "$basePath/$propertyId/$groupIndex",
					] );
				}

				$statementPropertyId = $statement['property']['id'] ?? null;
				if ( $statementPropertyId && $statementPropertyId !== (string)$propertyId ) {
					return new ValidationError( self::CODE_PROPERTY_ID_MISMATCH, [
						self::CONTEXT_PATH => "$propertyId/$groupIndex/property/id",
						self::CONTEXT_PROPERTY_ID_KEY => $propertyId,
						self::CONTEXT_PROPERTY_ID_VALUE => $statementPropertyId,
					] );
				}

				$validationError = $this->statementValidator->validate( $statement, "$basePath/$propertyId/$groupIndex" );
				if ( $validationError ) {
					return $validationError;
				}

				$deserializedStatements[] = $this->statementValidator->getValidatedStatement();
			}
		}

		$this->deserializedStatements = new StatementList( ...$deserializedStatements );

		return null;
	}

	private function findInGroup( array $statementGroup, string $statementId ): ?array {
		foreach ( $statementGroup as $statement ) {
			if ( $statement['id'] === $statementId ) {
				return $statement;
			}
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
