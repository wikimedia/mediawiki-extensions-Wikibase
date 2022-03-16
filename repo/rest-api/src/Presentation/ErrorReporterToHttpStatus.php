<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Presentation;

use Wikibase\Repo\RestApi\Domain\Model\ErrorReporter;

/**
 * @license GPL-2.0-or-later
 */
class ErrorReporterToHttpStatus {
	/**
	 * @var array
	 */
	private static $lookupTable = [
		'item-not-found' => 404,
	];

	public static function lookup( ErrorReporter $error ): int {
		return self::$lookupTable[ $error->getCode() ];
	}
}
