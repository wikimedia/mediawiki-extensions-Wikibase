<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement;

use LogicException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;

/**
 * @license GPL-2.0-or-later
 */
class ReplaceStatementValidator {

	private StatementIdValidator $statementIdValidator;
	private EditMetadataValidator $editMetadataValidator;
	private StatementValidator $statementValidator;

	public function __construct(
		StatementIdValidator $statementIdValidator,
		StatementValidator $statementValidator,
		EditMetadataValidator $editMetadataValidator
	) {
		$this->statementIdValidator = $statementIdValidator;
		$this->statementValidator = $statementValidator;
		$this->editMetadataValidator = $editMetadataValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function assertValidRequest( ReplaceStatementRequest $request ): void {
		$this->validateStatementId( $request->getStatementId() );
		$this->validateStatement( $request->getStatement() );
		$this->validateEditTags( $request->getEditTags() );
		$this->validateComment( $request->getComment() );
	}

	public function getValidatedStatement(): Statement {
		return $this->statementValidator->getValidatedStatement();
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateStatementId( string $statementId ): void {
		$validationError = $this->statementIdValidator->validate( $statementId );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_STATEMENT_ID,
				"Not a valid statement ID: {$validationError->getContext()[StatementIdValidator::CONTEXT_VALUE]}"
			);
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateStatement( array $statement ): void {
		$validationError = $this->statementValidator->validate( $statement );
		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case StatementValidator::CODE_INVALID_FIELD:
					throw new UseCaseError(
						UseCaseError::STATEMENT_DATA_INVALID_FIELD,
						"Invalid input for '{$context[StatementValidator::CONTEXT_FIELD_NAME]}'",
						[
							UseCaseError::CONTEXT_PATH => $context[StatementValidator::CONTEXT_FIELD_NAME],
							UseCaseError::CONTEXT_VALUE => $context[StatementValidator::CONTEXT_FIELD_VALUE],
						]
					);
				case StatementValidator::CODE_MISSING_FIELD:
					throw new UseCaseError(
						UseCaseError::STATEMENT_DATA_MISSING_FIELD,
						"Mandatory field missing in the statement data: {$context[StatementValidator::CONTEXT_FIELD_NAME]}",
						[ UseCaseError::CONTEXT_PATH => $context[StatementValidator::CONTEXT_FIELD_NAME] ]
					);
				default:
					throw new LogicException( "Unknown validation error code: {$validationError->getCode()}" );
			}
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateEditTags( array $editTags ): void {
		$validationError = $this->editMetadataValidator->validateEditTags( $editTags );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_EDIT_TAG,
				"Invalid MediaWiki tag: {$validationError->getContext()[EditMetadataValidator::CONTEXT_TAG_VALUE]}"
			);
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateComment( ?string $comment ): void {
		if ( $comment === null ) {
			return;
		}

		$validationError = $this->editMetadataValidator->validateComment( $comment );
		if ( $validationError ) {
			$commentMaxLength = $validationError->getContext()[EditMetadataValidator::CONTEXT_COMMENT_MAX_LENGTH];
			throw new UseCaseError(
				UseCaseError::COMMENT_TOO_LONG,
				"Comment must not be longer than $commentMaxLength characters."
			);
		}
	}

}
