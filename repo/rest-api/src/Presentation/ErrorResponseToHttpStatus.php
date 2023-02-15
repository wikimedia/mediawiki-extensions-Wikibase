<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Presentation;

use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @license GPL-2.0-or-later
 */
class ErrorResponseToHttpStatus {
	/**
	 * @var array
	 */
	private static array $lookupTable = [
		ErrorResponse::INVALID_ITEM_ID => 400,
		ErrorResponse::INVALID_PROPERTY_ID => 400,
		ErrorResponse::INVALID_STATEMENT_ID => 400,
		ErrorResponse::INVALID_FIELD => 400,
		ErrorResponse::COMMENT_TOO_LONG => 400,
		ErrorResponse::INVALID_EDIT_TAG => 400,
		ErrorResponse::STATEMENT_DATA_INVALID_FIELD => 400,
		ErrorResponse::STATEMENT_DATA_MISSING_FIELD => 400,
		ErrorResponse::INVALID_OPERATION_CHANGED_STATEMENT_ID => 400,
		ErrorResponse::INVALID_OPERATION_CHANGED_PROPERTY => 400,
		ErrorResponse::INVALID_PATCH => 400,
		ErrorResponse::INVALID_PATCH_OPERATION => 400,
		ErrorResponse::INVALID_PATCH_FIELD_TYPE => 400,
		ErrorResponse::MISSING_JSON_PATCH_FIELD => 400,
		ErrorResponse::PERMISSION_DENIED => 403,
		ErrorResponse::ITEM_NOT_FOUND => 404,
		ErrorResponse::ITEM_REDIRECTED => 409,
		ErrorResponse::STATEMENT_NOT_FOUND => 404,
		ErrorResponse::PATCHED_STATEMENT_INVALID_FIELD => 422,
		ErrorResponse::PATCHED_STATEMENT_MISSING_FIELD => 422,
		ErrorResponse::PATCH_TEST_FAILED => 409,
		ErrorResponse::PATCH_TARGET_NOT_FOUND => 409,
		ErrorResponse::UNEXPECTED_ERROR => 500,
	];

	public static function lookup( ErrorResponse $error ): int {
		return self::$lookupTable[ $error->getCode() ];
	}
}
