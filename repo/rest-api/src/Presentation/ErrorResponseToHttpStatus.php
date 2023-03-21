<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Presentation;

use Wikibase\Repo\RestApi\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class ErrorResponseToHttpStatus {
	/**
	 * @var array
	 */
	private static array $lookupTable = [
		UseCaseError::INVALID_ITEM_ID => 400,
		UseCaseError::INVALID_PROPERTY_ID => 400,
		UseCaseError::INVALID_STATEMENT_ID => 400,
		UseCaseError::INVALID_FIELD => 400,
		UseCaseError::INVALID_LANGUAGE_CODE => 400,
		UseCaseError::COMMENT_TOO_LONG => 400,
		UseCaseError::INVALID_EDIT_TAG => 400,
		UseCaseError::STATEMENT_DATA_INVALID_FIELD => 400,
		UseCaseError::STATEMENT_DATA_MISSING_FIELD => 400,
		UseCaseError::INVALID_OPERATION_CHANGED_STATEMENT_ID => 400,
		UseCaseError::INVALID_OPERATION_CHANGED_PROPERTY => 400,
		UseCaseError::INVALID_PATCH => 400,
		UseCaseError::INVALID_PATCH_OPERATION => 400,
		UseCaseError::INVALID_PATCH_FIELD_TYPE => 400,
		UseCaseError::MISSING_JSON_PATCH_FIELD => 400,
		UseCaseError::PERMISSION_DENIED => 403,
		UseCaseError::ITEM_NOT_FOUND => 404,
		UseCaseError::LABEL_NOT_FOUND => 404,
		UseCaseError::ALIAS_NOT_DEFINED => 404,
		UseCaseError::ITEM_REDIRECTED => 409,
		UseCaseError::STATEMENT_NOT_FOUND => 404,
		UseCaseError::PATCHED_STATEMENT_INVALID_FIELD => 422,
		UseCaseError::PATCHED_STATEMENT_MISSING_FIELD => 422,
		UseCaseError::PATCH_TEST_FAILED => 409,
		UseCaseError::PATCH_TARGET_NOT_FOUND => 409,
		UseCaseError::UNEXPECTED_ERROR => 500,
	];

	public static function lookup( string $errorCode ): int {
		return self::$lookupTable[ $errorCode ];
	}
}
