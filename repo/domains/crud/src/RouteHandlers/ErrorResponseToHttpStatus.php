<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\RouteHandlers;

use LogicException;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

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
		UseCaseError::REFERENCED_RESOURCE_NOT_FOUND => 400,
		UseCaseError::RESOURCE_TOO_LARGE => 400,
		UseCaseError::STATEMENT_GROUP_PROPERTY_ID_MISMATCH => 400,
		UseCaseError::VALUE_TOO_LONG => 400,

		// 403 errors:
		UseCaseError::PERMISSION_DENIED => 403,

		// 404 errors:
		UseCaseError::RESOURCE_NOT_FOUND => 404,

		// 409 errors:
		UseCaseError::ITEM_REDIRECTED => 409,
		UseCaseError::PATCH_TARGET_NOT_FOUND => 409,
		UseCaseError::PATCH_TEST_FAILED => 409,

		// 422 errors:
		UseCaseError::DATA_POLICY_VIOLATION => 422,
		UseCaseError::PATCH_RESULT_INVALID_KEY => 422,
		UseCaseError::PATCH_RESULT_INVALID_VALUE => 422,
		UseCaseError::PATCH_RESULT_MISSING_FIELD => 422,
		UseCaseError::PATCH_RESULT_MODIFIED_READ_ONLY_VALUE => 422,
		UseCaseError::PATCH_RESULT_REFERENCED_RESOURCE_NOT_FOUND => 422,
		UseCaseError::PATCH_RESULT_VALUE_TOO_LONG => 422,
		UseCaseError::PATCHED_STATEMENT_GROUP_PROPERTY_ID_MISMATCH => 422,

		// 429 errors:
		UseCaseError::REQUEST_LIMIT_REACHED => 429,
	];

	public static function lookup( string $errorCode ): int {
		if ( !array_key_exists( $errorCode, self::$lookupTable ) ) {
			throw new LogicException( "Error code '$errorCode' not found in lookup table" );
		}
		return self::$lookupTable[$errorCode];
	}
}
