<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Presentation;

use Wikibase\Repo\RestApi\UseCases\UseCaseException;

/**
 * @license GPL-2.0-or-later
 */
class ErrorResponseToHttpStatus {
	/**
	 * @var array
	 */
	private static array $lookupTable = [
		UseCaseException::INVALID_ITEM_ID => 400,
		UseCaseException::INVALID_PROPERTY_ID => 400,
		UseCaseException::INVALID_STATEMENT_ID => 400,
		UseCaseException::INVALID_FIELD => 400,
		UseCaseException::COMMENT_TOO_LONG => 400,
		UseCaseException::INVALID_EDIT_TAG => 400,
		UseCaseException::STATEMENT_DATA_INVALID_FIELD => 400,
		UseCaseException::STATEMENT_DATA_MISSING_FIELD => 400,
		UseCaseException::INVALID_OPERATION_CHANGED_STATEMENT_ID => 400,
		UseCaseException::INVALID_OPERATION_CHANGED_PROPERTY => 400,
		UseCaseException::INVALID_PATCH => 400,
		UseCaseException::INVALID_PATCH_OPERATION => 400,
		UseCaseException::INVALID_PATCH_FIELD_TYPE => 400,
		UseCaseException::MISSING_JSON_PATCH_FIELD => 400,
		UseCaseException::PERMISSION_DENIED => 403,
		UseCaseException::ITEM_NOT_FOUND => 404,
		UseCaseException::ITEM_REDIRECTED => 409,
		UseCaseException::STATEMENT_NOT_FOUND => 404,
		UseCaseException::PATCHED_STATEMENT_INVALID_FIELD => 422,
		UseCaseException::PATCHED_STATEMENT_MISSING_FIELD => 422,
		UseCaseException::PATCH_TEST_FAILED => 409,
		UseCaseException::PATCH_TARGET_NOT_FOUND => 409,
		UseCaseException::UNEXPECTED_ERROR => 500,
	];

	public static function lookup( string $errorCode ): int {
		return self::$lookupTable[ $errorCode ];
	}
}
