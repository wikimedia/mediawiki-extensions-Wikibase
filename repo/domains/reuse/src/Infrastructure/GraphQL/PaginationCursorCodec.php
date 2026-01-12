<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\InvalidSearchCursor;

/**
 * @license GPL-2.0-or-later
 */
trait PaginationCursorCodec {
	private function encodeOffsetAsCursor( int $offset ): string {
		return base64_encode( (string)$offset );
	}

	/**
	 * @throws InvalidSearchCursor
	 */
	private function decodeOffsetFromCursor( string $cursor ): int {
		$offsetString = base64_decode( $cursor );
		if ( !ctype_digit( $offsetString ) ) {
			throw new InvalidSearchCursor();
		}

		return (int)$offsetString;
	}
}
