<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatement;

use LogicException;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementErrorResponse extends ErrorResponse {

	public static function newFromValidationError( ValidationError $validationError ): self {
		$errorSource = $validationError->getSource();
		switch ( $errorSource ) {
			case GetItemStatementValidator::SOURCE_STATEMENT_ID:
				return new self(
					ErrorResponse::INVALID_STATEMENT_ID,
					"Not a valid statement ID: {$validationError->getValue()}"
				);

			case GetItemStatementValidator::SOURCE_ITEM_ID:
				return new self(
					ErrorResponse::INVALID_ITEM_ID,
					"Not a valid item ID: " . $validationError->getValue()
				);

			default:
				throw new LogicException( "Unexpected validation error source: $errorSource" );
		}
	}
}
