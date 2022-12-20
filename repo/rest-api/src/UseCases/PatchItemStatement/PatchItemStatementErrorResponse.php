<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\PatchItemStatement;

use LogicException;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\JsonPatchValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemStatementErrorResponse extends ErrorResponse {

	public static function newFromValidationError( ValidationError $validationError ): self {
		$errorCode = $validationError->getCode();
		$context = $validationError->getContext();
		switch ( $errorCode ) {
			case ItemIdValidator::CODE_INVALID:
				return new self(
					ErrorResponse::INVALID_ITEM_ID,
					'Not a valid item ID: ' . $context[ItemIdValidator::ERROR_CONTEXT_VALUE]
				);

			case StatementIdValidator::CODE_INVALID:
				return new self(
					ErrorResponse::INVALID_STATEMENT_ID,
					'Not a valid statement ID: ' . $context[StatementIdValidator::ERROR_CONTEXT_VALUE]
				);

			case JsonPatchValidator::CODE_INVALID:
				return new self(
					ErrorResponse::INVALID_PATCH,
					'The provided patch is invalid'
				);

			case JsonPatchValidator::CODE_INVALID_OPERATION:
				$op = $context[JsonPatchValidator::ERROR_CONTEXT_OPERATION]['op'];
				return new self(
					ErrorResponse::INVALID_PATCH_OPERATION,
					"Incorrect JSON patch operation: '$op'",
					$context
				);

			case JsonPatchValidator::CODE_INVALID_FIELD_TYPE:
				$field = $context[JsonPatchValidator::ERROR_CONTEXT_FIELD];
				return new self(
					ErrorResponse::INVALID_PATCH_FIELD_TYPE,
					"The value of '$field' must be of type string",
					$context
				);

			case JsonPatchValidator::CODE_MISSING_FIELD:
				$field = $context[JsonPatchValidator::ERROR_CONTEXT_FIELD];
				return new self(
					ErrorResponse::MISSING_JSON_PATCH_FIELD,
					"Missing '$field' in JSON patch",
					$context
				);

			case EditMetadataValidator::CODE_INVALID_TAG:
				return new self(
					ErrorResponse::INVALID_EDIT_TAG,
					"Invalid MediaWiki tag: {$context[EditMetadataValidator::ERROR_CONTEXT_TAG_VALUE]}"
				);

			case EditMetadataValidator::CODE_COMMENT_TOO_LONG:
				$commentMaxLength = $context[EditMetadataValidator::ERROR_CONTEXT_COMMENT_MAX_LENGTH];
				return new self(
					ErrorResponse::COMMENT_TOO_LONG,
					"Comment must not be longer than $commentMaxLength characters."
				);

			case StatementValidator::CODE_INVALID:
				return new self(
					ErrorResponse::PATCHED_STATEMENT_INVALID,
					'The patch results in an invalid statement which cannot be stored'
				);

			case StatementValidator::CODE_MISSING_FIELD:
				return new self(
					ErrorResponse::PATCHED_STATEMENT_MISSING_FIELD,
					"Mandatory field missing in the patched statement: {$context[StatementValidator::CONTEXT_FIELD_NAME]}",
					[
						'path' => $context[StatementValidator::CONTEXT_FIELD_NAME],
					]
				);

			case StatementValidator::CODE_INVALID_FIELD:
				return new self(
					ErrorResponse::PATCHED_STATEMENT_INVALID_FIELD,
					"Invalid input for '{$context[StatementValidator::CONTEXT_FIELD_NAME]}' in the patched statement",
					[
						'path' => $context[StatementValidator::CONTEXT_FIELD_NAME],
						'value' => $context[StatementValidator::CONTEXT_FIELD_VALUE]
					]
				);

			default:
				throw new LogicException( "Unexpected validation error code: $errorCode" );
		}
	}
}
