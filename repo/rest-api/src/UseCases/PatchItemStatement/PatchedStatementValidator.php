<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\PatchItemStatement;

use LogicException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Validation\StatementValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchedStatementValidator {

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
			$errorCode = $validationError->getCode();
			$context = $validationError->getContext();
			switch ( $errorCode ) {
				case StatementValidator::CODE_MISSING_FIELD:
					throw new UseCaseError(
						UseCaseError::PATCHED_STATEMENT_MISSING_FIELD,
						"Mandatory field missing in the patched statement: {$context[StatementValidator::CONTEXT_FIELD_NAME]}",
						[
							'path' => $context[StatementValidator::CONTEXT_FIELD_NAME],
						]
					);

				case StatementValidator::CODE_INVALID_FIELD:
					throw new UseCaseError(
						UseCaseError::PATCHED_STATEMENT_INVALID_FIELD,
						"Invalid input for '{$context[StatementValidator::CONTEXT_FIELD_NAME]}' in the patched statement",
						[
							'path' => $context[StatementValidator::CONTEXT_FIELD_NAME],
							'value' => $context[StatementValidator::CONTEXT_FIELD_VALUE],
						]
					);

				default:
					throw new LogicException( "Unexpected validation error code: $errorCode" );
			}
		}

		return $this->statementValidator->getValidatedStatement();
	}

}
