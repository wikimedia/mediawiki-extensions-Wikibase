<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\RouteHandlers;

use LogicException;
use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class ErrorResponseToHttpStatus {

	private static array $lookupTable = [
		// 400 errors:
		UseCaseError::INVALID_QUERY_PARAMETER => 400,
	];

	public static function lookup( string $errorCode ): int {
		if ( !array_key_exists( $errorCode, self::$lookupTable ) ) {
			throw new LogicException( "Error code '$errorCode' not found in lookup table" );
		}
		return self::$lookupTable[$errorCode];
	}
}
