<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\PatchItemStatement;

use LogicException;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\JsonPatchValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

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
	 * @throws UseCaseException
	 */
	public function assertValidRequest( PatchItemStatementRequest $request ): void {
		$validationError = $this->validateItemId( $request->getItemId() ) ?:
			$this->statementIdValidator->validate( $request->getStatementId() ) ?:
				$this->jsonPatchValidator->validate( $request->getPatch() ) ?:
					$this->editMetadataValidator->validateEditTags( $request->getEditTags() ) ?:
						$this->editMetadataValidator->validateComment( $request->getComment() );

		if ( $validationError ) {
			$this::throwUseCaseExceptionFromValidationError( $validationError );
		}
	}

	private function validateItemId( ?string $itemId ): ?ValidationError {
		return $itemId ? $this->itemIdValidator->validate( $itemId ) : null;
	}

	/**
	 * @throws UseCaseException
	 */
	public static function throwUseCaseExceptionFromValidationError( ValidationError $validationError ): void {
		$errorCode = $validationError->getCode();
		$context = $validationError->getContext();
		switch ( $errorCode ) {
			case ItemIdValidator::CODE_INVALID:
				throw new UseCaseException(
					ErrorResponse::INVALID_ITEM_ID,
					'Not a valid item ID: ' . $context[ItemIdValidator::CONTEXT_VALUE]
				);

			case StatementIdValidator::CODE_INVALID:
				throw new UseCaseException(
					ErrorResponse::INVALID_STATEMENT_ID,
					'Not a valid statement ID: ' . $context[StatementIdValidator::CONTEXT_VALUE]
				);

			case JsonPatchValidator::CODE_INVALID:
				throw new UseCaseException(
					ErrorResponse::INVALID_PATCH,
					'The provided patch is invalid'
				);

			case JsonPatchValidator::CODE_INVALID_OPERATION:
				$op = $context[JsonPatchValidator::CONTEXT_OPERATION]['op'];
				throw new UseCaseException(
					ErrorResponse::INVALID_PATCH_OPERATION,
					"Incorrect JSON patch operation: '$op'",
					$context
				);

			case JsonPatchValidator::CODE_INVALID_FIELD_TYPE:
				$field = $context[JsonPatchValidator::CONTEXT_FIELD];
				throw new UseCaseException(
					ErrorResponse::INVALID_PATCH_FIELD_TYPE,
					"The value of '$field' must be of type string",
					$context
				);

			case JsonPatchValidator::CODE_MISSING_FIELD:
				$field = $context[JsonPatchValidator::CONTEXT_FIELD];
				throw new UseCaseException(
					ErrorResponse::MISSING_JSON_PATCH_FIELD,
					"Missing '$field' in JSON patch",
					$context
				);

			case EditMetadataValidator::CODE_INVALID_TAG:
				throw new UseCaseException(
					ErrorResponse::INVALID_EDIT_TAG,
					"Invalid MediaWiki tag: {$context[EditMetadataValidator::CONTEXT_TAG_VALUE]}"
				);

			case EditMetadataValidator::CODE_COMMENT_TOO_LONG:
				$commentMaxLength = $context[EditMetadataValidator::CONTEXT_COMMENT_MAX_LENGTH];
				throw new UseCaseException(
					ErrorResponse::COMMENT_TOO_LONG,
					"Comment must not be longer than $commentMaxLength characters."
				);

			case StatementValidator::CODE_MISSING_FIELD:
				throw new UseCaseException(
					ErrorResponse::PATCHED_STATEMENT_MISSING_FIELD,
					"Mandatory field missing in the patched statement: {$context[StatementValidator::CONTEXT_FIELD_NAME]}",
					[
						'path' => $context[StatementValidator::CONTEXT_FIELD_NAME],
					]
				);

			case StatementValidator::CODE_INVALID_FIELD:
				throw new UseCaseException(
					ErrorResponse::PATCHED_STATEMENT_INVALID_FIELD,
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
}
