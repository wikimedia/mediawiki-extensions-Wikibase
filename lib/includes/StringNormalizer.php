<?php
 /**
 *
 * Copyright © 03.07.13 by the authors listed below.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @license GPL 2+
 * @file
 *
 * @author Daniel Kinzler
 * @author John Erling Blad < jeblad@gmail.com >
 */


namespace Wikibase;


use UtfNormal;

/**
 * StringNormalizer provides several methods for normalizing strings.
 *
 * @since 0.4
 *
 * @package Wikibase
 */
class StringNormalizer {

	/**
	 * Remove bytes that represent an incomplete Unicode character
	 * at the end of string (e.g. bytes of the char are missing)
	 *
	 * @todo: this was stolen from the Language class. Make that code reusable.
	 *
	 * @param $string String
	 * @return string
	 */
	protected function removeBadCharLast( $string ) {
		if ( $string != '' ) {
			$char = ord( $string[strlen( $string ) - 1] );
			$m = array();
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
	 * @todo: this was stolen from the Language class. Make that code reusable.
	 *
	 * @param $string String
	 * @return string
	 */
	protected function removeBadCharFirst( $string ) {
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
	 *
	 * @param $string
	 *
	 * @return string
	 */
	public function trimBadChars( $string ) {
		$string = $this->removeBadCharFirst( $string );
		$string = $this->removeBadCharLast( $string );
		return $string;
	}

	/**
	 * Trim initial and trailing whitespace and control chars, and optionally compress internal ones.
	 *
	 * @param string $inputString The actual string to process.
	 *
	 * @return string where whitespace possibly are removed.
	 */
	public function trimWhitespace( $inputString ) {
		$inputString = $this->trimBadChars( $inputString );

		// \p{Z} - whitespace
		// \p{Cc} - control chars
		// WARNING: *any* invalid UTF8 sequence causes preg_replace to return an empty string.
		$trimmed = preg_replace( '/^[\p{Z}\p{Cc}]+|[\p{Z}\p{Cc}]+$/u', '', $inputString );
		$trimmed = preg_replace( '/[\p{Cc}]+/u', ' ', $trimmed );
		return $trimmed;
	}

	/**
	 * Normalize string into NFC by using the cleanup metod from UtfNormal.
	 *
	 * @param string $inputString The actual string to process.
	 *
	 * @return string where whitespace possibly are removed.
	 */
	public function cleanupToNFC( $inputString ) {
		$cleaned = $inputString;
		$cleaned = $this->trimBadChars( $cleaned );
		$cleaned = UtfNormal::cleanUp( $cleaned );
		return $cleaned;
	}

	/**
	 * Do a cleanupToNFC after the string is trimmed
	 *
	 * @param string $inputString
	 *
	 * @return string on NFC form
	 */
	public function trimToNFC( $inputString ) {
		return $this->cleanupToNFC( $this->trimWhitespace( $inputString ) );
	}


}