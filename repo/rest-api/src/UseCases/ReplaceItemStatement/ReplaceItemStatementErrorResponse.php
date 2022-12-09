<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement;

use LogicException;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatementErrorResponse extends ErrorResponse {

	public static function newFromValidationError( ValidationError $validationError ): self {
		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
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

			case StatementValidator::CODE_INVALID:
				return new self(
					ErrorResponse::INVALID_STATEMENT_DATA,
					'Invalid statement data provided'
				);

			case StatementValidator::CODE_INVALID_FIELD:
				return new self(
					ErrorResponse::INVALID_STATEMENT_DATA_FIELD,
					"Invalid input for ${context[StatementValidator::CONTEXT_FIELD_NAME]}",
					[
						'path' => $context[StatementValidator::CONTEXT_FIELD_NAME],
						'value' => $context[StatementValidator::CONTEXT_FIELD_VALUE],
					]
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

			default:
				throw new LogicException( "Unexpected validation error code: {$validationError->getCode()}" );
		}
	}
}
