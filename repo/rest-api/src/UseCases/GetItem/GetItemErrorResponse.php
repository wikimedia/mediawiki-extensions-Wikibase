<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class GetItemErrorResponse extends ErrorResponse {

	public static function newFromValidationError( ValidationError $validationError ): self {
		$errorCode = $validationError->getCode();
		switch ( $errorCode ) {
			case ItemIdValidator::CODE_INVALID:
				return new self(
					ErrorResponse::INVALID_ITEM_ID,
					'Not a valid item ID: ' . $validationError->getContext()[ItemIdValidator::ERROR_CONTEXT_VALUE]
				);

			case GetItemValidator::CODE_INVALID_FIELD:
				return new self(
					ErrorResponse::INVALID_FIELD,
					'Not a valid field: ' . $validationError->getContext()[GetItemValidator::ERROR_CONTEXT_FIELD_VALUE]
				);

			default:
				throw new \LogicException( "Unexpected validation error code: $errorCode" );
		}
	}
}
