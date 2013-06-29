<?php

namespace Wikibase\Test;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\MultiLangSerializationOptions;
use Wikibase\LanguageUtils;

/**
 * Tests for the Wikibase\SerializationOptions class.
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
 * @file
 * @since 0.2
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SerializationOptionsTest extends \MediaWikiTestCase {

	public function testSerializationOptionsConstructor() {
		new SerializationOptions();
		$this->assertTrue( true );
	}

	public function testMultiLangSerializationOptionsConstructor() {
		new MultiLangSerializationOptions();
		$this->assertTrue( true );
	}

	private function preprocessTestMultiLangSerializationOptionsLanguages( $languages ) {
		if ( $languages === null ) {
			return null;
		}

		foreach ( $languages as $languageKey => &$languageValue ) {
			if ( !is_numeric( $languageKey ) ) {
				$languageValue = LanguageUtils::getFallbackChain(
					\Language::factory( $languageKey ), $languageValue
				);
			}
		}

		return $languages;
	}

	/**
	 * @dataProvider provideTestMultiLangSerializationOptionsLanguages
	 */
	public function testMultiLangSerializationOptionsLanguages( $languages, $codes, $fallbackChains ) {
		$languages = $this->preprocessTestMultiLangSerializationOptionsLanguages( $languages );
		$fallbackChains = $this->preprocessTestMultiLangSerializationOptionsLanguages( $fallbackChains );

		$options = new MultiLangSerializationOptions();
		$options->setLanguages( $languages );

		$this->assertEquals( $codes, $options->getLanguages() );
		$this->assertEquals( $fallbackChains, $options->getLanguageFallbackChains() );
	}

	public function provideTestMultiLangSerializationOptionsLanguages() {
		return array(
			array( null, null, null ),
			array( array( 'en' ), array( 'en' ), array( 'en' => LanguageUtils::FALLBACK_SELF ) ),
			array( array( 'en', 'de' ), array( 'en', 'de' ), array(
				'en' => LanguageUtils::FALLBACK_SELF, 'de' => LanguageUtils::FALLBACK_SELF
			) ),
			array(
				array( 'en', 'zh' => LanguageUtils::FALLBACK_SELF | LanguageUtils::FALLBACK_VARIANTS ),
				array( 'en', 'zh' ),
				array(
					'en' => LanguageUtils::FALLBACK_SELF,
					'zh' => LanguageUtils::FALLBACK_SELF | LanguageUtils::FALLBACK_VARIANTS,
				),
			),
			array(
				array(
					'de-formal' => LanguageUtils::FALLBACK_OTHERS,
					'sr' => LanguageUtils::FALLBACK_SELF | LanguageUtils::FALLBACK_VARIANTS,
				),
				array( 'de-formal', 'sr' ),
				array(
					'de-formal' => LanguageUtils::FALLBACK_OTHERS,
					'sr' => LanguageUtils::FALLBACK_SELF | LanguageUtils::FALLBACK_VARIANTS,
				),
			),
		);
	}

}
