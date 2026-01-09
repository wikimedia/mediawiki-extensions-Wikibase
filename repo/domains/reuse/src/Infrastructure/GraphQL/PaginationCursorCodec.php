<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

/**
 * @license GPL-2.0-or-later
 */
trait PaginationCursorCodec {
	private function encodeOffsetAsCursor( int $offset ): string {
		return base64_encode( (string)$offset );
	}

	private function decodeOffsetFromCursor( string $cursor ): int {
		return (int)base64_decode( $cursor ); // TODO throw if invalid
	}
}
