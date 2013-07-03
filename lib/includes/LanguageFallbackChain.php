<?php

namespace Wikibase;

/**
 * Object representing a language fallback chain used in Wikibase.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 */
class LanguageFallbackChain {

	/**
	 * @var LanguageWithConversion[]
	 */
	private $chain = array();

	/**
	 * Constructor
	 *
	 * @param LanguageWithConversion[] $chain
	 */
	public function __construct( array $chain ) {
		$this->chain = $chain;
	}

	/**
	 * Get raw fallback chain as an array. Semi-private for testing.
	 *
	 * @return LanguageWithConversion[]
	 */
	public function getFallbackChain() {
		return $this->chain;
	}

	/**
	 * Try to fetch the best value in a multilingual data array.
	 *
	 * @param string[] $data Multilingual data with language codes as keys
	 *
	 * @return null|array of three items: array(
	 * 	'value' => finally fetched and translated value
	 * 	'language' => language code of the language which final value is in
	 * 	'source' => language code of the language where the value is fetched
	 * ), or null when no data can be found.
	 */
	public function extractPreferredValue( $data ) {

		foreach ( $this->chain as $languageWithConversion ) {
			$fetchCode = $languageWithConversion->getFetchLanguage()->getCode();
			$languageCode = $languageWithConversion->getLanguage()->getCode();

			if ( isset( $data[$fetchCode] ) ) {
				return array(
					'value' => $languageWithConversion->translate( $data[$fetchCode] ),
					'language' => $languageCode,
					'source' => $fetchCode,
				);
			}
		}

		return null;
	}
}
