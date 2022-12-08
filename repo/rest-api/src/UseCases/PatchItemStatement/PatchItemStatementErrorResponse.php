<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\PatchItemStatement;

use LogicException;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\JsonPatchValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemStatementErrorResponse extends ErrorResponse {

	public static function newFromValidationError( ValidationError $validationError ): self {
		$errorCode = $validationError->getCode();
		switch ( $errorCode ) {
			case ItemIdValidator::CODE_INVALID:
				return new self(
					ErrorResponse::INVALID_ITEM_ID,
					'Not a valid item ID: ' . $validationError->getContext()[ItemIdValidator::ERROR_CONTEXT_VALUE]
				);

			case StatementIdValidator::CODE_INVALID:
				return new self(
					ErrorResponse::INVALID_STATEMENT_ID,
					'Not a valid statement ID: ' . $validationError->getContext()[StatementIdValidator::ERROR_CONTEXT_VALUE]
				);

			case JsonPatchValidator::CODE_INVALID:
				return new self(
					ErrorResponse::INVALID_PATCH,
					'The provided patch is invalid'
				);

			case JsonPatchValidator::CODE_INVALID_OPERATION:
				$op = $validationError->getContext()[JsonPatchValidator::ERROR_CONTEXT_OPERATION]['op'];
				return new self(
					ErrorResponse::INVALID_PATCH_OPERATION,
					"Incorrect JSON patch operation: '$op'",
					$validationError->getContext()
				);

			case JsonPatchValidator::CODE_INVALID_FIELD_TYPE:
				$field = $validationError->getContext()[JsonPatchValidator::ERROR_CONTEXT_FIELD];
				return new self(
					ErrorResponse::INVALID_PATCH_FIELD_TYPE,
					"The value of '$field' must be of type string",
					$validationError->getContext()
				);

			case JsonPatchValidator::CODE_MISSING_FIELD:
				$field = $validationError->getContext()[JsonPatchValidator::ERROR_CONTEXT_FIELD];
				return new self(
					ErrorResponse::MISSING_JSON_PATCH_FIELD,
					"Missing '$field' in JSON patch",
					$validationError->getContext()
				);

			case EditMetadataValidator::CODE_INVALID_TAG:
				return new self(
					ErrorResponse::INVALID_EDIT_TAG,
					"Invalid MediaWiki tag: {$validationError->getContext()[EditMetadataValidator::ERROR_CONTEXT_TAG_VALUE]}"
				);

			case EditMetadataValidator::CODE_COMMENT_TOO_LONG:
				$commentMaxLength = $validationError->getContext()[EditMetadataValidator::ERROR_CONTEXT_COMMENT_MAX_LENGTH];
				return new self(
					ErrorResponse::COMMENT_TOO_LONG,
					"Comment must not be longer than $commentMaxLength characters."
				);

			default:
				throw new LogicException( "Unexpected validation error code: $errorCode" );
		}
	}
}
