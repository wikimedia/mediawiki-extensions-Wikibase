<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement;

use LogicException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchedStatementValidator {

	public const CONTEXT_PATH = 'path';
	public const CONTEXT_VALUE = 'value';

	private StatementValidator $statementValidator;

	public function __construct( StatementValidator $statementValidator ) {
		$this->statementValidator = $statementValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserializeStatement( array $patchedStatement ): Statement {
		$validationError = $this->statementValidator->validate( $patchedStatement );
		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case StatementValidator::CODE_MISSING_FIELD:
					throw new UseCaseError(
						UseCaseError::PATCHED_STATEMENT_MISSING_FIELD,
						"Mandatory field missing in the patched statement: {$context[StatementValidator::CONTEXT_FIELD_NAME]}",
						[ self::CONTEXT_PATH => $context[StatementValidator::CONTEXT_FIELD_NAME] ]
					);

				case StatementValidator::CODE_INVALID_FIELD:
					throw new UseCaseError(
						UseCaseError::PATCHED_STATEMENT_INVALID_FIELD,
						"Invalid input for '{$context[StatementValidator::CONTEXT_FIELD_NAME]}' in the patched statement",
						[
							self::CONTEXT_PATH => $context[StatementValidator::CONTEXT_FIELD_NAME],
							self::CONTEXT_VALUE => $context[StatementValidator::CONTEXT_FIELD_VALUE],
						]
					);

				default:
					throw new LogicException( "Unexpected validation error code: {$validationError->getCode()}" );
			}
		}

		// @phan-suppress-next-line PhanTypeMismatchReturnNullable statement is valid and not-null
		return $this->statementValidator->getValidatedStatement();
	}

}
