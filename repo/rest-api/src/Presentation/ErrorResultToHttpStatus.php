<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Presentation;

use Wikibase\Repo\RestApi\UseCases\ErrorResult;

/**
 * @license GPL-2.0-or-later
 */
class ErrorResultToHttpStatus {
	/**
	 * @var array
	 */
	private static $lookupTable = [
		ErrorResult::INVALID_ITEM_ID => 400,
		ErrorResult::ITEM_NOT_FOUND => 404,
		ErrorResult::UNEXPECTED_ERROR => 500
	];

	public static function lookup( ErrorResult $error ): int {
		return self::$lookupTable[ $error->getCode() ];
	}
}
