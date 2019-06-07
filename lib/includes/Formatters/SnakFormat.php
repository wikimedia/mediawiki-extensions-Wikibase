<?php

namespace Wikibase\Lib\Formatters;

use InvalidArgumentException;

/**
 * Helper for handling SnakFormatter output formats.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class SnakFormat {

	/**
	 * Get the format to fallback to, in case a given format is not available.
	 *
	 * @param string $format One of the SnakFormatter::FORMAT_... constants.
	 *
	 * @throws InvalidArgumentException
	 * @return string One of the SnakFormatter::FORMAT_* constants.
	 */
	public function getFallbackFormat( $format ) {
		switch ( $format ) {
			case SnakFormatter::FORMAT_HTML:
			case SnakFormatter::FORMAT_HTML_DIFF:
			case SnakFormatter::FORMAT_HTML_VERBOSE:
				return SnakFormatter::FORMAT_HTML;
			case SnakFormatter::FORMAT_HTML_VERBOSE_PREVIEW:
				return SnakFormatter::FORMAT_HTML_VERBOSE;
			case SnakFormatter::FORMAT_WIKI:
			case SnakFormatter::FORMAT_PLAIN:
				return $format;
		}

		throw new InvalidArgumentException( 'Unsupported output format: ' . $format );
	}

	/**
	 * Get the fallback chain for a given format. The returned array contains the
	 * SnakFormatter::FORMAT_* constants to use, from best fitting to worst.
	 *
	 * @param string $format Either of the SnakFormatter::FORMAT_* constants.
	 * @return string[] SnakFormatter::FORMAT_* constants, starting with the most preferred format
	 */
	public function getFallbackChain( $format ) {
		$chain = [ $format ];

		do {
			$lastFormat = end( $chain );
			$newFormat = $this->getFallbackFormat( $lastFormat );
			if ( $newFormat !== $lastFormat ) {
				$chain[] = $newFormat;
			}
		} while ( $newFormat !== $lastFormat );

		// We touch the array pointer above, reset it just to be sure.
		reset( $chain );
		return $chain;
	}

	/**
	 * Get the base output format, for a given format.
	 *
	 * @param string $format
	 * @return string One of the SnakFormatter::FORMAT_HTML/FORMAT_WIKI/FORMAT_PLAIN constants.
	 */
	public function getBaseFormat( $format ) {
		$chain = $this->getFallbackChain( $format );

		return end( $chain );
	}

	/**
	 * Can the given target format be served by the available format.
	 *
	 * @param string $availableFormat
	 * @param string $targetFormat
	 * @return bool Whether $availableFormat can be served by $targetFormat.
	 */
	public function isPossibleFormat( $availableFormat, $targetFormat ) {
		return in_array( $availableFormat, $this->getFallbackChain( $targetFormat ) );
	}

}

/** @deprecated */
class_alias( SnakFormat::class, 'Wikibase\Lib\SnakFormat' );
