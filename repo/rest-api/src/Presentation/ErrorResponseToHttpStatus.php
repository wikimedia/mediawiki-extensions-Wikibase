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
	private static $lookupTable = [
		ErrorResponse::INVALID_ITEM_ID => 400,
		ErrorResponse::INVALID_STATEMENT_ID => 400,
		ErrorResponse::INVALID_FIELD => 400,
		ErrorResponse::COMMENT_TOO_LONG => 400,
		ErrorResponse::INVALID_EDIT_TAG => 400,
		ErrorResponse::INVALID_STATEMENT_DATA => 400,
		ErrorResponse::INVALID_OPERATION_CHANGED_STATEMENT_ID => 400,
		ErrorResponse::INVALID_OPERATION_CHANGED_PROPERTY => 400,
		ErrorResponse::PERMISSION_DENIED => 403,
		ErrorResponse::ITEM_NOT_FOUND => 404,
		ErrorResponse::ITEM_REDIRECTED => 409,
		ErrorResponse::STATEMENT_NOT_FOUND => 404,
		ErrorResponse::UNEXPECTED_ERROR => 500
	];

	public static function lookup( ErrorResponse $error ): int {
		return self::$lookupTable[ $error->getCode() ];
	}
}
