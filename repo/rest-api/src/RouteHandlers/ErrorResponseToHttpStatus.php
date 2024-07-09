<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use LogicException;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class ErrorResponseToHttpStatus {

	private static array $lookupTable = [
		UseCaseError::INVALID_PATH_PARAMETER => 400,
		UseCaseError::INVALID_QUERY_PARAMETER => 400,
		UseCaseError::INVALID_LANGUAGE_CODE => 400,
		UseCaseError::PATCHED_LABEL_INVALID_LANGUAGE_CODE => 422,
		UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_SAME_VALUE => 422,
		UseCaseError::PATCHED_PROPERTY_LABEL_DESCRIPTION_SAME_VALUE => 422,
		UseCaseError::PATCHED_PROPERTY_INVALID_FIELD => 422,
		UseCaseError::PATCHED_PROPERTY_INVALID_OPERATION_CHANGE_PROPERTY_DATATYPE => 422,
		UseCaseError::PATCHED_PROPERTY_INVALID_OPERATION_CHANGE_PROPERTY_ID => 422,
		UseCaseError::PATCHED_PROPERTY_MISSING_FIELD => 422,
		UseCaseError::PATCHED_PROPERTY_UNEXPECTED_FIELD => 422,
		UseCaseError::COMMENT_TOO_LONG => 400,
		UseCaseError::INVALID_VALUE => 400,
		UseCaseError::SITELINK_CONFLICT => 409,
		UseCaseError::SITELINK_NOT_DEFINED => 404,
		UseCaseError::STATEMENT_DATA_MISSING_FIELD => 400,
		UseCaseError::CANNOT_MODIFY_READ_ONLY_VALUE => 400,
		UseCaseError::INVALID_OPERATION_CHANGED_STATEMENT_ID => 400,
		UseCaseError::INVALID_OPERATION_CHANGED_PROPERTY => 400,
		UseCaseError::INVALID_PATCH => 400,
		UseCaseError::MISSING_JSON_PATCH_FIELD => 400,
		UseCaseError::INVALID_LABEL => 400,
		UseCaseError::PATCHED_LABEL_INVALID => 422,
		UseCaseError::DESCRIPTION_TOO_LONG => 400,
		UseCaseError::LABEL_DESCRIPTION_SAME_VALUE => 400,
		UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE => 400,
		UseCaseError::PROPERTY_LABEL_DUPLICATE => 400,
		UseCaseError::ALIAS_TOO_LONG => 400,
		UseCaseError::INVALID_ALIAS_LIST => 400,
		UseCaseError::ALIAS_DUPLICATE => 400,
		UseCaseError::PATCHED_PROPERTY_LABEL_DUPLICATE => 422,
		UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_DUPLICATE => 422,
		UseCaseError::PATCHED_ITEM_INVALID_OPERATION_CHANGE_ITEM_ID => 422,
		UseCaseError::PATCHED_ITEM_UNEXPECTED_FIELD => 422,
		UseCaseError::PATCHED_ITEM_INVALID_FIELD => 422,
		UseCaseError::LABEL_TOO_LONG => 400,
		UseCaseError::ITEM_DATA_INVALID_FIELD => 400,
		UseCaseError::ITEM_DATA_UNEXPECTED_FIELD => 400,
		UseCaseError::ITEM_STATEMENT_ID_MISMATCH => 400,
		UseCaseError::PROPERTY_STATEMENT_ID_MISMATCH => 400,
		UseCaseError::PATCHED_LABEL_EMPTY => 422,
		UseCaseError::PATCHED_LABEL_TOO_LONG => 422,
		UseCaseError::PATCHED_ALIAS_EMPTY => 422,
		UseCaseError::PATCHED_ALIASES_INVALID_FIELD => 422,
		UseCaseError::PATCHED_ALIASES_INVALID_LANGUAGE_CODE => 422,
		UseCaseError::PATCHED_ALIAS_TOO_LONG => 422,
		UseCaseError::PATCHED_ALIAS_DUPLICATE => 422,
		UseCaseError::PERMISSION_DENIED => 403,
		UseCaseError::ITEM_NOT_FOUND => 404,
		UseCaseError::PROPERTY_NOT_FOUND => 404,
		UseCaseError::LABEL_NOT_DEFINED => 404,
		UseCaseError::ALIASES_NOT_DEFINED => 404,
		UseCaseError::DESCRIPTION_NOT_DEFINED => 404,
		UseCaseError::ITEM_REDIRECTED => 409,
		UseCaseError::STATEMENT_NOT_FOUND => 404,
		UseCaseError::PATCHED_DESCRIPTION_INVALID => 422,
		UseCaseError::PATCHED_DESCRIPTION_INVALID_LANGUAGE_CODE => 422,
		UseCaseError::PATCHED_DESCRIPTION_EMPTY => 422,
		UseCaseError::PATCHED_DESCRIPTION_TOO_LONG => 422,
		UseCaseError::PATCHED_STATEMENT_INVALID_FIELD => 422,
		UseCaseError::PATCHED_STATEMENT_MISSING_FIELD => 422,
		UseCaseError::PATCHED_SITELINK_INVALID_SITE_ID => 422,
		UseCaseError::PATCHED_SITELINK_INVALID_TITLE => 422,
		UseCaseError::PATCHED_SITELINK_MISSING_TITLE => 422,
		UseCaseError::PATCHED_SITELINK_TITLE_EMPTY => 422,
		UseCaseError::PATCHED_SITELINK_TITLE_DOES_NOT_EXIST => 422,
		UseCaseError::PATCHED_SITELINK_INVALID_BADGE => 422,
		UseCaseError::PATCHED_SITELINK_ITEM_NOT_A_BADGE => 422,
		UseCaseError::PATCHED_SITELINK_BADGES_FORMAT => 422,
		UseCaseError::PATCHED_SITELINK_CONFLICT => 422,
		UseCaseError::PATCHED_SITELINK_URL_NOT_MODIFIABLE => 422,
		UseCaseError::PATCH_TEST_FAILED => 409,
		UseCaseError::PATCH_TARGET_NOT_FOUND => 409,
		UseCaseError::UNEXPECTED_ERROR => 500,
		UseCaseError::ITEM_NOT_A_BADGE => 400,
		UseCaseError::SITELINK_DATA_MISSING_TITLE => 400,
		UseCaseError::SITELINK_TITLE_NOT_FOUND => 400,
		UseCaseError::INVALID_SITELINK_TYPE => 400,
		UseCaseError::STATEMENT_GROUP_PROPERTY_ID_MISMATCH => 400,
		UseCaseError::PATCHED_INVALID_STATEMENT_GROUP_TYPE => 422,
		UseCaseError::PATCHED_INVALID_STATEMENT_TYPE => 422,
		UseCaseError::PATCHED_STATEMENT_GROUP_PROPERTY_ID_MISMATCH => 422,
		UseCaseError::STATEMENT_ID_NOT_MODIFIABLE => 422,
		UseCaseError::PATCHED_STATEMENT_PROPERTY_NOT_MODIFIABLE => 422,
		UseCaseError::PATCHED_INVALID_SITELINK_TYPE => 422,
	];

	public static function lookup( string $errorCode ): int {
		if ( !array_key_exists( $errorCode, self::$lookupTable ) ) {
			throw new LogicException( "Error code '$errorCode' not found in lookup table" );
		}
		return self::$lookupTable[$errorCode];
	}
}
