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
	 * Trim initial and trailing whitespace and control chars, and optionally compress internal ones.
	 *
	 * @param string $inputString The actual string to process.
	 *
	 * @return string where whitespace possibly are removed.
	 */
	public function trimWhitespace( $inputString ) {
		// \p{Z} - whitespace
		// \p{Cc} - control chars
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
		return UtfNormal::cleanUp( $inputString );
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