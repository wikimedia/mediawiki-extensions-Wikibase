<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\PatchItemStatement;

use LogicException;
use Wikibase\Repo\RestApi\Domain\Services\JsonPatchValidator;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\PatchInvalidFieldTypeValidationError;
use Wikibase\Repo\RestApi\Validation\PatchInvalidOpValidationError;
use Wikibase\Repo\RestApi\Validation\PatchMissingFieldValidationError;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemStatementErrorResponse extends ErrorResponse {

	public static function newFromValidationError( ValidationError $validationError ): self {
		$errorSource = $validationError->getSource();
		switch ( $errorSource ) {
			case PatchItemStatementValidator::SOURCE_ITEM_ID:
				return new self(
					ErrorResponse::INVALID_ITEM_ID,
					"Not a valid item ID: " . $validationError->getContext()[ItemIdValidator::ERROR_CONTEXT_VALUE]
				);

			case PatchItemStatementValidator::SOURCE_STATEMENT_ID:
				return new self(
					ErrorResponse::INVALID_STATEMENT_ID,
					"Not a valid statement ID: " . $validationError->getContext()[StatementIdValidator::ERROR_CONTEXT_VALUE]
				);

			case PatchItemStatementValidator::SOURCE_PATCH:
				switch ( true ) {
					case $validationError instanceof PatchInvalidOpValidationError:
						$op = $validationError->getContext()[JsonPatchValidator::ERROR_CONTEXT_OPERATION]['op'];
						return new self(
							ErrorResponse::INVALID_PATCH_OPERATION,
							"Incorrect JSON patch operation: '$op'",
							$validationError->getContext()
						);
					case $validationError instanceof PatchInvalidFieldTypeValidationError:
						$field = $validationError->getContext()[JsonPatchValidator::ERROR_CONTEXT_FIELD];
						return new self(
							ErrorResponse::INVALID_PATCH_FIELD_TYPE,
							"The value of '$field' must be of type string",
							$validationError->getContext()
						);
					case $validationError instanceof PatchMissingFieldValidationError:
						$field = $validationError->getContext()[JsonPatchValidator::ERROR_CONTEXT_FIELD];
						return new self(
							ErrorResponse::MISSING_JSON_PATCH_FIELD,
							"Missing '$field' in JSON patch",
							$validationError->getContext()
						);
					default:
						return new self(
							ErrorResponse::INVALID_PATCH,
							"The provided patch is invalid"
						);
				}
			case PatchItemStatementValidator::SOURCE_EDIT_TAGS:
				return new self(
					ErrorResponse::INVALID_EDIT_TAG,
					"Invalid MediaWiki tag: {$validationError->getContext()[EditMetadataValidator::ERROR_CONTEXT_TAG_VALUE]}"
				);

			case PatchItemStatementValidator::SOURCE_COMMENT:
				$commentMaxLength = $validationError->getContext()[EditMetadataValidator::ERROR_CONTEXT_COMMENT_MAX_LENGTH];
				return new self(
					ErrorResponse::COMMENT_TOO_LONG,
					"Comment must not be longer than $commentMaxLength characters."
				);

			default:
				throw new LogicException( "Unexpected validation error source: $errorSource" );
		}
	}
}
