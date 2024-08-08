<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchStatement;

use LogicException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;

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
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case StatementValidator::CODE_MISSING_FIELD:
					throw UseCaseError::newMissingFieldInPatchResult(
						$context[StatementValidator::CONTEXT_PATH],
						$context[StatementValidator::CONTEXT_FIELD]
					);

				case StatementValidator::CODE_INVALID_FIELD:
					throw new UseCaseError(
						UseCaseError::PATCHED_STATEMENT_INVALID_FIELD,
						"Invalid input for '{$context[StatementValidator::CONTEXT_FIELD]}' in the patched statement",
						[
							UseCaseError::CONTEXT_PATH => $context[StatementValidator::CONTEXT_FIELD],
							UseCaseError::CONTEXT_VALUE => $context[StatementValidator::CONTEXT_VALUE],
						]
					);

				default:
					throw new LogicException( "Unexpected validation error code: {$validationError->getCode()}" );
			}
		}

		return $this->statementValidator->getValidatedStatement();
	}

}
