<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels;

use LogicException;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\JsonPatchValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemLabelsValidator {

	private ItemIdValidator $itemIdValidator;
	private JsonPatchValidator $jsonPatchValidator;
	private EditMetadataValidator $editMetadataValidator;

	public function __construct(
		ItemIdValidator $itemIdValidator,
		JsonPatchValidator $jsonPatchValidator,
		EditMetadataValidator $editMetadataValidator
	) {
		$this->itemIdValidator = $itemIdValidator;
		$this->jsonPatchValidator = $jsonPatchValidator;
		$this->editMetadataValidator = $editMetadataValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function assertValidRequest( PatchItemLabelsRequest $request ): void {
		$this->validateItemId( $request->getItemId() );
		$this->validatePatch( $request->getPatch() );
		$this->validateEditTags( $request->getEditTags() );
		$this->validateComment( $request->getComment() );
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateItemId( string $itemId ): void {
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
	private function validatePatch( array $patch ): void {
		$validationError = $this->jsonPatchValidator->validate( $patch );

		if ( $validationError ) {
			$errorCode = $validationError->getCode();
			$context = $validationError->getContext();

			switch ( $errorCode ) {
				case JsonPatchValidator::CODE_INVALID:
					throw new UseCaseError(
						UseCaseError::INVALID_PATCH,
						'The provided patch is invalid'
					);

				case JsonPatchValidator::CODE_INVALID_OPERATION:
					$op = $context[JsonPatchValidator::CONTEXT_OPERATION]['op'];
					throw new UseCaseError(
						UseCaseError::INVALID_PATCH_OPERATION,
						"Incorrect JSON patch operation: '$op'",
						$context
					);

				case JsonPatchValidator::CODE_INVALID_FIELD_TYPE:
					$field = $context[JsonPatchValidator::CONTEXT_FIELD];
					throw new UseCaseError(
						UseCaseError::INVALID_PATCH_FIELD_TYPE,
						"The value of '$field' must be of type string",
						$context
					);

				case JsonPatchValidator::CODE_MISSING_FIELD:
					$field = $context[JsonPatchValidator::CONTEXT_FIELD];
					throw new UseCaseError(
						UseCaseError::MISSING_JSON_PATCH_FIELD,
						"Missing '$field' in JSON patch",
						$context
					);

				default:
					throw new LogicException( "Unknown validation error code: $errorCode" );
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
				"Comment must not be longer than $commentMaxLength characters."
			);
		}
	}

}
