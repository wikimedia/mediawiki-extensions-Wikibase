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
		ErrorResult::ITEM_NOT_FOUND => 404,
	];

	public static function lookup( ErrorResult $error ): int {
		return self::$lookupTable[ $error->getCode() ];
	}
}
