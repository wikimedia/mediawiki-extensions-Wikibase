<?php

declare( strict_types = 1 );

namespace Wikibase\Lib;

use UtfNormal\Validator;

/**
 * StringNormalizer provides several methods for normalizing strings.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author John Erling Blad < jeblad@gmail.com >
 */
class StringNormalizer {

	/**
	 * Remove bytes that represent an incomplete Unicode character
	 * at the end of string (e.g. bytes of the char are missing)
	 *
	 * @todo this was stolen from the Language class. Make that code reusable.
	 */
	protected function removeBadCharLast( string $string ): string {
		if ( $string != '' ) {
			$char = ord( substr( $string, -1 ) );
			$m = [];
			if ( $char >= 0xc0 ) {
				# We got the first byte only of a multibyte char; remove it.
				$string = substr( $string, 0, -1 );
			} elseif ( $char >= 0x80 &&
				preg_match( '/^(.*)(?:[\xe0-\xef][\x80-\xbf]|' .
					'[\xf0-\xf7][\x80-\xbf]{1,2})$/', $string, $m )
			) {
				# We chopped in the middle of a character; remove it
				$string = $m[1];
			}
		}
		return $string;
	}

	/**
	 * Remove bytes that represent an incomplete Unicode character
	 * at the start of string (e.g. bytes of the char are missing)
	 *
	 * @todo this was stolen from the Language class. Make that code reusable.
	 */
	protected function removeBadCharFirst( string $string ): string {
		if ( $string != '' ) {
			$char = ord( $string[0] );
			if ( $char >= 0x80 && $char < 0xc0 ) {
				# We chopped in the middle of a character; remove the whole thing
				$string = preg_replace( '/^[\x80-\xbf]+/', '', $string );
			}
		}
		return $string;
	}

	/**
	 * Remove incomplete UTF-8 sequences from the beginning and end of the string.
	 */
	public function trimBadChars( string $string ): string {
		$string = $this->removeBadCharFirst( $string );
		$string = $this->removeBadCharLast( $string );
		return $string;
	}

	/**
	 * Trim initial and trailing whitespace and control chars, and compress some internal control chars.
	 */
	public function trimWhitespace( string $inputString ): string {
		$inputString = $this->trimBadChars( $inputString );

		// WARNING: *any* invalid UTF8 sequence causes preg_replace to return an empty string.
		// \p{Cc} only includes general control characters.
		$trimmed = preg_replace( '/\p{Cc}+/u', ' ', $inputString );
		// \p{Z} includes all whitespace characters and invisible separators.
		$trimmed = preg_replace( '/^\p{Z}+|\p{Z}+$/u', '', $trimmed );
		return $trimmed;
	}

	/**
	 * Normalize string into NFC by using the cleanup method from UtfNormal.
	 */
	public function cleanupToNFC( string $inputString ): string {
		$cleaned = $inputString;
		$cleaned = $this->trimBadChars( $cleaned );
		$cleaned = Validator::cleanUp( $cleaned );
		return $cleaned;
	}

	/**
	 * Do a cleanupToNFC after the string is trimmed
	 */
	public function trimToNFC( string $inputString ): string {
		return $this->cleanupToNFC( $this->trimWhitespace( $inputString ) );
	}

}
