<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Formatters;

/**
 * Helper for handling SnakFormatter output formats.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class SnakFormat {

	/**
	 * Get the base output format, for a given format.
	 *
	 * @param string $format
	 * @return string One of the SnakFormatter::FORMAT_HTML/FORMAT_WIKI/FORMAT_PLAIN constants.
	 */
	public function getBaseFormat( string $format ): string {
		switch ( $format ) {
			case SnakFormatter::FORMAT_HTML_DIFF:
			case SnakFormatter::FORMAT_HTML_VERBOSE:
			case SnakFormatter::FORMAT_HTML_VERBOSE_PREVIEW:
				return SnakFormatter::FORMAT_HTML;
			default:
				return $format;
		}
	}

	/**
	 * Can the given target format be served by the available format.
	 *
	 * @param string $availableFormat
	 * @param string $targetFormat
	 * @return bool Whether $availableFormat can be served by $targetFormat.
	 */
	public function isPossibleFormat( string $availableFormat, string $targetFormat ): bool {
		switch ( $targetFormat ) {
			case SnakFormatter::FORMAT_HTML_VERBOSE_PREVIEW:
				if ( $availableFormat === SnakFormatter::FORMAT_HTML_VERBOSE ) {
					return true;
				}
				// fall through
			case SnakFormatter::FORMAT_HTML_DIFF:
			case SnakFormatter::FORMAT_HTML_VERBOSE:
				if ( $availableFormat === SnakFormatter::FORMAT_HTML ) {
					return true;
				}
				break;
		}
		return $targetFormat === $availableFormat;
	}

}
