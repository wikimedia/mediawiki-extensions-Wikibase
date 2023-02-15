<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatements;

use LogicException;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementsErrorResponse extends ErrorResponse {

	public static function newFromValidationError( ValidationError $validationError ): self {
		$errorCode = $validationError->getCode();
		switch ( $errorCode ) {
			case ItemIdValidator::CODE_INVALID:
				return new self(
					ErrorResponse::INVALID_ITEM_ID,
					'Not a valid item ID: ' . $validationError->getContext()[ItemIdValidator::CONTEXT_VALUE]
				);
			case GetItemStatementsValidator::CODE_INVALID_PROPERTY_ID:
				return new self(
					ErrorResponse::INVALID_PROPERTY_ID,
					'Not a valid property ID: ' . $validationError->getContext()[GetItemStatementsValidator::CONTEXT_PROPERTY_ID_VALUE]
				);

			default:
				throw new LogicException( "Unexpected validation error code: $errorCode" );
		}
	}
}
