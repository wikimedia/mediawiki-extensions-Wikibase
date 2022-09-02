<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\PatchItemStatement;

use LogicException;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
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
					"Not a valid item ID: " . $validationError->getValue()
				);

			case PatchItemStatementValidator::SOURCE_STATEMENT_ID:
				return new self(
					ErrorResponse::INVALID_STATEMENT_ID,
					"Not a valid statement ID: {$validationError->getValue()}"
				);

			case PatchItemStatementValidator::SOURCE_PATCH:
				return new self(
					ErrorResponse::INVALID_PATCH,
					"The provided patch is invalid"
				);

			case PatchItemStatementValidator::SOURCE_EDIT_TAGS:
				return new self(
					ErrorResponse::INVALID_EDIT_TAG,
					"Invalid MediaWiki tag: " . $validationError->getValue()
				);

			case PatchItemStatementValidator::SOURCE_COMMENT:
				return new self(
					ErrorResponse::COMMENT_TOO_LONG,
					"Comment must not be longer than " . $validationError->getValue() . " characters."
				);

			default:
				throw new LogicException( "Unexpected validation error source: $errorSource" );
		}
	}
}
