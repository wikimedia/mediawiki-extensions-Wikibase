<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\AddItemStatement;

use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class AddItemStatementErrorResponse extends ErrorResponse {

	public static function newFromValidationError( ValidationError $validationError ): self {
		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case ItemIdValidator::CODE_INVALID:
				return new self(
					ErrorResponse::INVALID_ITEM_ID,
					'Not a valid item ID: ' . $context[ItemIdValidator::CONTEXT_VALUE]
				);

			case StatementValidator::CODE_INVALID:
				return new self(
					ErrorResponse::INVALID_STATEMENT_DATA,
					'Invalid statement data provided'
				);

			case StatementValidator::CODE_INVALID_FIELD:
				return new self(
					ErrorResponse::STATEMENT_DATA_INVALID_FIELD,
					"Invalid input for {$context[StatementValidator::CONTEXT_FIELD_NAME]}",
					[
						'path' => $context[StatementValidator::CONTEXT_FIELD_NAME],
						'value' => $context[StatementValidator::CONTEXT_FIELD_VALUE],
					]
				);

			case StatementValidator::CODE_MISSING_FIELD:
				return new self(
					ErrorResponse::STATEMENT_DATA_MISSING_FIELD,
					'Mandatory field missing in the statement data: ' .
					$context[StatementValidator::CONTEXT_FIELD_NAME],
					[ 'path' => $context[StatementValidator::CONTEXT_FIELD_NAME] ]
				);

			case EditMetadataValidator::CODE_COMMENT_TOO_LONG:
				$commentMaxLength = $context[EditMetadataValidator::CONTEXT_COMMENT_MAX_LENGTH];
				return new self(
					ErrorResponse::COMMENT_TOO_LONG,
					"Comment must not be longer than $commentMaxLength characters."
				);

			case EditMetadataValidator::CODE_INVALID_TAG:
				return new self(
					ErrorResponse::INVALID_EDIT_TAG,
					'Invalid MediaWiki tag: ' . $context[EditMetadataValidator::CONTEXT_TAG_VALUE]
				);

			default:
				throw new \LogicException( "Unexpected validation error code: {$validationError->getCode()}" );
		}
	}
}
