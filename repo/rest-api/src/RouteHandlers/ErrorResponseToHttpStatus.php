<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use LogicException;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class ErrorResponseToHttpStatus {

	private static array $lookupTable = [
		// 400 errors:
		UseCaseError::CANNOT_MODIFY_READ_ONLY_VALUE => 400,
		UseCaseError::INVALID_KEY => 400,
		UseCaseError::INVALID_PATH_PARAMETER => 400,
		UseCaseError::INVALID_QUERY_PARAMETER => 400,
		UseCaseError::INVALID_VALUE => 400,
		UseCaseError::ITEM_STATEMENT_ID_MISMATCH => 400,
		UseCaseError::MISSING_FIELD => 400,
		UseCaseError::PROPERTY_STATEMENT_ID_MISMATCH => 400,
		UseCaseError::SITELINK_TITLE_NOT_FOUND => 400,
		UseCaseError::STATEMENT_GROUP_PROPERTY_ID_MISMATCH => 400,
		UseCaseError::VALUE_TOO_LONG => 400,

		// 403 errors:
		UseCaseError::PERMISSION_DENIED => 403,

		// 404 errors:
		UseCaseError::ALIASES_NOT_DEFINED => 404,
		UseCaseError::DESCRIPTION_NOT_DEFINED => 404,
		UseCaseError::ITEM_NOT_FOUND => 404,
		UseCaseError::LABEL_NOT_DEFINED => 404,
		UseCaseError::PROPERTY_NOT_FOUND => 404,
		UseCaseError::SITELINK_NOT_DEFINED => 404,
		UseCaseError::STATEMENT_NOT_FOUND => 404,

		// 409 errors:
		UseCaseError::ITEM_REDIRECTED => 409,
		UseCaseError::PATCH_TARGET_NOT_FOUND => 409,
		UseCaseError::PATCH_TEST_FAILED => 409,

		// 422 errors:
		UseCaseError::DATA_POLICY_VIOLATION => 422,
		UseCaseError::PATCH_RESULT_INVALID_KEY => 422,
		UseCaseError::PATCH_RESULT_INVALID_VALUE => 422,
		UseCaseError::PATCH_RESULT_VALUE_TOO_LONG => 422,
		UseCaseError::PATCH_RESULT_MISSING_FIELD => 422,
		UseCaseError::PATCH_RESULT_MODIFIED_READ_ONLY_VALUE => 422,
		UseCaseError::PATCHED_INVALID_SITELINK_TYPE => 422,
		UseCaseError::PATCHED_ITEM_INVALID_OPERATION_CHANGE_ITEM_ID => 422,
		UseCaseError::PATCHED_SITELINK_TITLE_DOES_NOT_EXIST => 422,
		UseCaseError::PATCHED_SITELINK_URL_NOT_MODIFIABLE => 422,
		UseCaseError::PATCHED_STATEMENT_GROUP_PROPERTY_ID_MISMATCH => 422,
		UseCaseError::PATCHED_STATEMENT_PROPERTY_NOT_MODIFIABLE => 422,
		UseCaseError::STATEMENT_ID_NOT_MODIFIABLE => 422,

		// 500 errors:
		UseCaseError::UNEXPECTED_ERROR => 500,
	];

	public static function lookup( string $errorCode ): int {
		if ( !array_key_exists( $errorCode, self::$lookupTable ) ) {
			throw new LogicException( "Error code '$errorCode' not found in lookup table" );
		}
		return self::$lookupTable[$errorCode];
	}
}
