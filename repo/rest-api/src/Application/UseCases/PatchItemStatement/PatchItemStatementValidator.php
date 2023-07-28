<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement;

use LogicException;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\JsonPatchValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemStatementValidator {

	private ItemIdValidator $itemIdValidator;
	private StatementIdValidator $statementIdValidator;
	private JsonPatchValidator $jsonPatchValidator;
	private EditMetadataValidator $editMetadataValidator;

	public function __construct(
		ItemIdValidator $itemIdValidator,
		StatementIdValidator $statementIdValidator,
		JsonPatchValidator $jsonPatchValidator,
		EditMetadataValidator $editMetadataValidator
	) {
		$this->itemIdValidator = $itemIdValidator;
		$this->statementIdValidator = $statementIdValidator;
		$this->jsonPatchValidator = $jsonPatchValidator;
		$this->editMetadataValidator = $editMetadataValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function assertValidRequest( PatchItemStatementRequest $request ): void {
		$this->validateItemId( $request->getItemId() );
		$this->validateStatementId( $request->getStatementId() );
		$this->validatePatch( $request->getPatch() );
		$this->validateEditTags( $request->getEditTags() );
		$this->validateComment( $request->getComment() );
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateItemId( ?string $itemId ): void {
		if ( $itemId === null ) {
			return;
		}

		$validationError = $this->itemIdValidator->validate( $itemId );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_ITEM_ID,
				"Not a valid item ID: {$validationError->getContext()[ItemIdValidator::CONTEXT_VALUE]}"
			);
		}
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
	private function validatePatch( array $patch ): void {
		$validationError = $this->jsonPatchValidator->validate( $patch );
		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case JsonPatchValidator::CODE_INVALID:
					throw new UseCaseError( UseCaseError::INVALID_PATCH, 'The provided patch is invalid' );
				case JsonPatchValidator::CODE_INVALID_OPERATION:
					$op = $context[JsonPatchValidator::CONTEXT_OPERATION]['op'];
					throw new UseCaseError(
						UseCaseError::INVALID_PATCH_OPERATION,
						"Incorrect JSON patch operation: '$op'",
						[ UseCaseError::CONTEXT_OPERATION => $context[JsonPatchValidator::CONTEXT_OPERATION] ]
					);
				case JsonPatchValidator::CODE_INVALID_FIELD_TYPE:
					throw new UseCaseError(
						UseCaseError::INVALID_PATCH_FIELD_TYPE,
						"The value of '{$context[JsonPatchValidator::CONTEXT_FIELD]}' must be of type string",
						[
							UseCaseError::CONTEXT_OPERATION => $context[JsonPatchValidator::CONTEXT_OPERATION],
							UseCaseError::CONTEXT_FIELD => $context[JsonPatchValidator::CONTEXT_FIELD],
						]
					);
				case JsonPatchValidator::CODE_MISSING_FIELD:
					throw new UseCaseError(
						UseCaseError::MISSING_JSON_PATCH_FIELD,
						"Missing '{$context[JsonPatchValidator::CONTEXT_FIELD]}' in JSON patch",
						[
							UseCaseError::CONTEXT_OPERATION => $context[JsonPatchValidator::CONTEXT_OPERATION],
							UseCaseError::CONTEXT_FIELD => $context[JsonPatchValidator::CONTEXT_FIELD],
						]
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
