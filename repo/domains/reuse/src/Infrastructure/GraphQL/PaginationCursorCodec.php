<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\GraphQLError;

/**
 * @license GPL-2.0-or-later
 */
trait PaginationCursorCodec {
	private function encodeOffsetAsCursor( int $offset ): string {
		return base64_encode(
			// padding the offset so that cursors have a more consistent length
			str_pad( (string)$offset, 10, '0', STR_PAD_LEFT )
		);
	}

	/**
	 * @throws GraphQLError
	 */
	private function decodeOffsetFromCursor( string $cursor ): int {
		$offsetString = base64_decode( $cursor );
		if ( !ctype_digit( $offsetString ) ) {
			throw GraphQLError::invalidSearchCursor();
		}

		return (int)$offsetString;
	}
}
