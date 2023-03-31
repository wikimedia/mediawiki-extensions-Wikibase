<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement;

use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddItemStatementValidator {

	private StatementValidator $statementValidator;
	private ItemIdValidator $itemIdValidator;
	private EditMetadataValidator $editMetadataValidator;

	public function __construct(
		ItemIdValidator $itemIdValidator,
		StatementValidator $statementValidator,
		EditMetadataValidator $editMetadataValidator
	) {
		$this->itemIdValidator = $itemIdValidator;
		$this->statementValidator = $statementValidator;
		$this->editMetadataValidator = $editMetadataValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function assertValidRequest( AddItemStatementRequest $request ): void {
		$this->validateItemId( $request->getItemId() );
		$this->validateStatement( $request->getStatement() );
		$this->validateEditTags( $request->getEditTags() );
		$this->validateComment( $request->getComment() );
	}

	public function getValidatedStatement(): ?Statement {
		return $this->statementValidator->getValidatedStatement();
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateItemId( ?string $itemId ): void {
		if ( !isset( $itemId ) ) {
			return;
		}

		$validationError = $this->itemIdValidator->validate( $itemId );

		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_ITEM_ID,
				'Not a valid item ID: ' . $validationError->getContext()[ItemIdValidator::CONTEXT_VALUE]
			);
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateStatement( array $statement ): void {
		$validationError = $this->statementValidator->validate( $statement );

		if ( $validationError ) {
			switch ( $validationError->getCode() ) {
				case StatementValidator::CODE_INVALID_FIELD:
					throw new UseCaseError(
						UseCaseError::STATEMENT_DATA_INVALID_FIELD,
						"Invalid input for '{$validationError->getContext()[StatementValidator::CONTEXT_FIELD_NAME]}'",
						[
							'path' => $validationError->getContext()[StatementValidator::CONTEXT_FIELD_NAME],
							'value' => $validationError->getContext()[StatementValidator::CONTEXT_FIELD_VALUE],
						]
					);
				case StatementValidator::CODE_MISSING_FIELD:
					throw new UseCaseError(
						UseCaseError::STATEMENT_DATA_MISSING_FIELD,
						'Mandatory field missing in the statement data: ' .
						$validationError->getContext()[StatementValidator::CONTEXT_FIELD_NAME],
						[ 'path' => $validationError->getContext()[StatementValidator::CONTEXT_FIELD_NAME] ]
					);
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
		if ( !isset( $comment ) ) {
			return;
		}

		$validationError = $this->editMetadataValidator->validateComment( $comment );

		if ( $validationError ) {
			$commentMaxLength = $validationError->getContext()[EditMetadataValidator::CONTEXT_COMMENT_MAX_LENGTH];
			throw new UseCaseError(
				UseCaseError::COMMENT_TOO_LONG,
				"Comment must not be longer than $commentMaxLength characters.",
			);
		}
	}
}
