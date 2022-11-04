<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\RemoveItemStatement;

use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class RemoveItemStatementErrorResponse extends ErrorResponse {

	public static function newFromValidationError( ValidationError $validationError ): self {
		$errorSource = $validationError->getSource();
		switch ( $errorSource ) {
			case RemoveItemStatementValidator::SOURCE_ITEM_ID:
				return new self(
					ErrorResponse::INVALID_ITEM_ID,
					"Not a valid item ID: " . $validationError->getContext()[ItemIdValidator::ERROR_CONTEXT_VALUE]
				);

			case RemoveItemStatementValidator::SOURCE_STATEMENT_ID:
				return new self(
					ErrorResponse::INVALID_STATEMENT_ID,
					"Not a valid statement ID: " . $validationError->getContext()[StatementIdValidator::ERROR_CONTEXT_VALUE]
				);

			case RemoveItemStatementValidator::SOURCE_COMMENT:
				$commentMaxLength = $validationError->getContext()[EditMetadataValidator::ERROR_CONTEXT_COMMENT_MAX_LENGTH];
				return new self(
					ErrorResponse::COMMENT_TOO_LONG,
					"Comment must not be longer than $commentMaxLength characters."
				);

			case RemoveItemStatementValidator::SOURCE_EDIT_TAGS:
				return new self(
					ErrorResponse::INVALID_EDIT_TAG,
					"Invalid MediaWiki tag: {$validationError->getContext()[EditMetadataValidator::ERROR_CONTEXT_TAG_VALUE]}"
				);

			default:
				throw new \LogicException( "Unexpected validation error source: $errorSource" );
		}
	}
}
