<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class ErrorResponseToHttpStatus {

	private static array $lookupTable = [
		UseCaseError::INVALID_ITEM_ID => 400,
		UseCaseError::INVALID_PROPERTY_ID => 400,
		UseCaseError::INVALID_STATEMENT_ID => 400,
		UseCaseError::INVALID_FIELD => 400,
		UseCaseError::INVALID_LANGUAGE_CODE => 400,
		UseCaseError::PATCHED_LABEL_INVALID_LANGUAGE_CODE => 422,
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
		UseCaseError::INVALID_LABEL => 400,
		UseCaseError::PATCHED_LABEL_INVALID => 422,
		UseCaseError::DESCRIPTION_EMPTY => 400,
		UseCaseError::DESCRIPTION_TOO_LONG => 400,
		UseCaseError::INVALID_DESCRIPTION => 400,
		UseCaseError::LABEL_DESCRIPTION_SAME_VALUE => 400,
		UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE => 400,
		UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_DUPLICATE => 422,
		UseCaseError::LABEL_EMPTY => 400,
		UseCaseError::LABEL_TOO_LONG => 400,
		UseCaseError::PATCHED_LABEL_EMPTY => 422,
		UseCaseError::PATCHED_LABEL_TOO_LONG => 422,
		UseCaseError::PERMISSION_DENIED => 403,
		UseCaseError::ITEM_NOT_FOUND => 404,
		UseCaseError::LABEL_NOT_DEFINED => 404,
		UseCaseError::ALIASES_NOT_DEFINED => 404,
		UseCaseError::DESCRIPTION_NOT_DEFINED => 404,
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
