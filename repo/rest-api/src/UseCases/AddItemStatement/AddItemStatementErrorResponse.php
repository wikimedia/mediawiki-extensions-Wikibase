<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\AddItemStatement;

use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class AddItemStatementErrorResponse extends ErrorResponse {

	public static function newFromValidationError( ValidationError $validationError ): self {
		$errorSource = $validationError->getSource();
		switch ( $errorSource ) {
			case AddItemStatementValidator::SOURCE_ITEM_ID:
				return new self(
					ErrorResponse::INVALID_ITEM_ID,
					"Not a valid item ID: " . $validationError->getValue()
				);

			case AddItemStatementValidator::SOURCE_STATEMENT:
				return new self(
					ErrorResponse::INVALID_STATEMENT_DATA,
					"Invalid statement data provided"
				);

			case AddItemStatementValidator::SOURCE_EDIT_TAGS:
				return new self(
					ErrorResponse::INVALID_EDIT_TAG,
					"Invalid MediaWiki tag: " . $validationError->getValue()
				);

			default:
				throw new \LogicException( "Unexpected validation error source: $errorSource" );
		}
	}
}
