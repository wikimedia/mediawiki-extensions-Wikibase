<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement;

use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementValidator;

/**
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatementValidator {

	private ItemIdValidator $itemIdValidator;
	private StatementIdValidator $statementIdValidator;
	private EditMetadataValidator $editMetadataValidator;
	private StatementValidator $statementValidator;

	public function __construct(
		ItemIdValidator $itemIdValidator,
		StatementIdValidator $statementIdValidator,
		StatementValidator $statementValidator,
		EditMetadataValidator $editMetadataValidator
	) {
		$this->itemIdValidator = $itemIdValidator;
		$this->statementIdValidator = $statementIdValidator;
		$this->statementValidator = $statementValidator;
		$this->editMetadataValidator = $editMetadataValidator;
	}

	/**
	 * @throws UseCaseException
	 */
	public function assertValidRequest( ReplaceItemStatementRequest $request ): void {
		$this->validateItemId( $request->getItemId() );
		$this->validateStatementId( $request->getStatementId() );
		$this->validateStatement( $request->getStatement() );
		$this->validateEditTags( $request->getEditTags() );
		$this->validateComment( $request->getComment() );
	}

	public function getValidatedStatement(): ?Statement {
		return $this->statementValidator->getValidatedStatement();
	}

	/**
	 * @throws UseCaseException
	 */
	private function validateItemId( ?string $itemId ): void {
		if ( !isset( $itemId ) ) {
			return;
		}

		$validationError = $this->itemIdValidator->validate( $itemId );

		if ( $validationError ) {
			throw new UseCaseException(
				ErrorResponse::INVALID_ITEM_ID,
				'Not a valid item ID: ' . $validationError->getContext()[ItemIdValidator::CONTEXT_VALUE]
			);
		}
	}

	/**
	 * @throws UseCaseException
	 */
	private function validateStatementId( string $statementId ): void {
		$validationError = $this->statementIdValidator->validate( $statementId );

		if ( $validationError ) {
			throw new UseCaseException(
				ErrorResponse::INVALID_STATEMENT_ID,
				'Not a valid statement ID: ' . $validationError->getContext()[StatementIdValidator::CONTEXT_VALUE]
			);
		}
	}

	/**
	 * @throws UseCaseException
	 */
	private function validateStatement( array $statement ): void {
		$validationError = $this->statementValidator->validate( $statement );

		if ( $validationError ) {
			switch ( $validationError->getCode() ) {
				case StatementValidator::CODE_INVALID_FIELD:
					throw new UseCaseException(
						ErrorResponse::STATEMENT_DATA_INVALID_FIELD,
						"Invalid input for '{$validationError->getContext()[StatementValidator::CONTEXT_FIELD_NAME]}'",
						[
							'path' => $validationError->getContext()[StatementValidator::CONTEXT_FIELD_NAME],
							'value' => $validationError->getContext()[StatementValidator::CONTEXT_FIELD_VALUE],
						]
					);
				case StatementValidator::CODE_MISSING_FIELD:
					throw new UseCaseException(
						ErrorResponse::STATEMENT_DATA_MISSING_FIELD,
						'Mandatory field missing in the statement data: ' .
						$validationError->getContext()[StatementValidator::CONTEXT_FIELD_NAME],
						[ 'path' => $validationError->getContext()[StatementValidator::CONTEXT_FIELD_NAME] ]
					);
			}
		}
	}

	/**
	 * @throws UseCaseException
	 */
	private function validateEditTags( array $editTags ): void {
		$validationError = $this->editMetadataValidator->validateEditTags( $editTags );

		if ( $validationError ) {
			throw new UseCaseException(
				ErrorResponse::INVALID_EDIT_TAG,
				"Invalid MediaWiki tag: {$validationError->getContext()[EditMetadataValidator::CONTEXT_TAG_VALUE]}"
			);
		}
	}

	/**
	 * @throws UseCaseException
	 */
	private function validateComment( ?string $comment ): void {
		if ( !isset( $comment ) ) {
			return;
		}

		$validationError = $this->editMetadataValidator->validateComment( $comment );

		if ( $validationError ) {
			$commentMaxLength = $validationError->getContext()[EditMetadataValidator::CONTEXT_COMMENT_MAX_LENGTH];
			throw new UseCaseException(
				ErrorResponse::COMMENT_TOO_LONG,
				"Comment must not be longer than $commentMaxLength characters."
			);
		}
	}

}
