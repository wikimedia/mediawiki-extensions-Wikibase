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
	 * @param mixed $patchedStatement
	 *
	 * @throws UseCaseError
	 */
	public function validateAndDeserializeStatement( $patchedStatement ): Statement {
		if (
			!is_array( $patchedStatement ) || ( count( $patchedStatement ) && array_is_list( $patchedStatement ) ) ) {
			throw UseCaseError::newPatchResultInvalidValue( '', $patchedStatement );
		}

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
					throw UseCaseError::newPatchResultInvalidValue(
						$context[StatementValidator::CONTEXT_PATH],
						$context[StatementValidator::CONTEXT_VALUE]
					);
				default:
					throw new LogicException( "Unexpected validation error code: {$validationError->getCode()}" );
			}
		}

		return $this->statementValidator->getValidatedStatement();
	}

}
