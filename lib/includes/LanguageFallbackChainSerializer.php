<?php

namespace Wikibase;
use Language, FormatJson, MWException;

/**
 * Object serializing LanguageFallbackChain objects in Wikibase.
 *
 * The serialized form is not for persistant storage. They only allows
 * clients to "echo" back the original fallback chain as a string.
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
class LanguageFallbackChainSerializer {

	/**
	 * Serialize a language fallback chain into a string. JSON is used now, but
	 * any external component is not expected to understand the serialized form.
	 *
	 * @param LanguageFallbackChain $languageFallbackChain
	 *
	 * @return string serialized form
	 */
	public function serialize( LanguageFallbackChain $languageFallbackChain ) {
		wfProfileIn( __METHOD__ );
		$data = array();

		// We sacrifice readability for length here, as the serialized form may be passed around in URLs.
		foreach ( $languageFallbackChain->getFallbackChain() as $item ) {
			$elem = array( $item->getLanguage()->getCode() );

			$sourceLanguage = $item->getSourceLanguage();
			if ( $sourceLanguage ) {
				$elem[] = $sourceLanguage->getCode();
			}

			$data[] = implode( ':', $elem );
		}

		wfProfileOut( __METHOD__ );
		return implode( '|', $data );
	}

	/**
	 * Unserialize a language fallback chain from a previous serializeLanguageFallbackChain output.
	 *
	 * @param string $serialized
	 *
	 * @return LanguageFallbackChain|null on bad input
	 */
	public function unserialize( $serialized ) {
		wfProfileIn( __METHOD__ );
		$data = explode( '|', $serialized );
		$chain = array();

		foreach ( $data as $itemString ) {
			$item = explode( ':', $itemString );
			$langCode = $item[0];

			if ( !isset( $item[1] ) ) {
				$sourceLangCode = false;
			} else {
				$sourceLangCode = $item[1];
			}

			try {
				$language = Language::factory( $langCode );
				if ( $sourceLangCode ) {
					$sourceLanguage = Language::factory( $sourceLangCode );
				} else {
					$sourceLanguage = null;
				}
				$chainItem = LanguageWithConversion::factory( $language, $sourceLanguage );
			} catch ( MWException $e ) {
				wfProfileOut( __METHOD__ );
				return null;
			}

			$chain[] = $chainItem;
		}

		$languageFallbackChain = new LanguageFallbackChain( $chain );
		wfProfileOut( __METHOD__ );
		return $languageFallbackChain;
	}

}
