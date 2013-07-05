<?php

namespace Wikibase\Test;

use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Serializers\MultiLangSerializationOptions;
use Wikibase\Lib\Serializers\MultilingualSerializer;

/**
 * @covers Wikibase\Lib\Serializers\MultilingualSerializer
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
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 */
class MultilingualSerializerTest extends \PHPUnit_Framework_TestCase {

	public function provideSerialize() {
		$validArgs = array();

		$options = new MultiLangSerializationOptions();
		$options->setUseKeys( true );
		$values = array(
			"en" => "capital city of Italy",
			"de" => "Hauptstadt von Italien",
			"it" => "",
			"fi" => "kunta Italiassa",
		);
		$expectedSerialization = array(
			"en" => array(
				"language" => "en",
				"value" => "capital city of Italy"
			),
			"de" => array(
				"language" => "de",
				"value" => "Hauptstadt von Italien"
			),
			"it" => array(
				"language" => "it",
				"removed" => ""
			),
			"fi" => array(
				"language" => "fi",
				"value" => "kunta Italiassa"
			),
		);
		$validArgs[] = array( $values, $options, $expectedSerialization );

		$options = new MultiLangSerializationOptions();
		$options->setUseKeys( false );
		$values = array(
			"en" => "capital city of Italy",
			"de" => "Hauptstadt von Italien",
			"it" => "capitale della Repubblica Italiana",
			"fi" => "kunta Italiassa",
		);
		$expectedSerialization = array(
			array(
				"language" => "en",
				"value" => "capital city of Italy"
			),
			array(
				"language" => "de",
				"value" => "Hauptstadt von Italien"
			),
			array(
				"language" => "it",
				"value" => "capitale della Repubblica Italiana"
			),
			array(
				"language" => "fi",
				"value" => "kunta Italiassa"
			),
		);
		$validArgs[] = array( $values, $options, $expectedSerialization );

		$options = new MultiLangSerializationOptions();
		$options->setUseKeys( true );
		$values = array(
			"en" => "Rome",
			"de-formal" => array(
				"value" => "Rom",
				"language" => "de",
				"source" => null,
			),
			"it" => "",
			"zh-tw" => array(
				"value" => "羅馬",
				"language" => "zh-tw",
				"source" => "zh-cn",
			),
			"sr-ec" => array(
				"value" => "Rome",
				"language" => "en",
				"source" => "en",
			),
		);
		$expectedSerialization = array(
			"en" => array(
				"language" => "en",
				"value" => "Rome"
			),
			"de-formal" => array(
				"language" => "de",
				"value" => "Rom"
			),
			"it" => array(
				"language" => "it",
				"removed" => ""
			),
			"zh-tw" => array(
				"language" => "zh-tw",
				"source-language" => "zh-cn",
				"value" => "羅馬"
			),
			"sr-ec" => array(
				"language" => "en",
				"source-language" => "en",
				"value" => "Rome"
			),
		);
		$validArgs[] = array( $values, $options, $expectedSerialization );

		$options = new MultiLangSerializationOptions();
		$options->setUseKeys( false );
		$values = array(
			"en" => "Rome",
			"de-formal" => array(
				"value" => "Rom",
				"language" => "de",
				"source" => null,
			),
			"it" => "",
			"fr" => array(
				"value" => "",
				"language" => "fr",
				"source" => null,
			),
			"zh-tw" => array(
				"value" => "羅馬",
				"language" => "zh-tw",
				"source" => "zh-cn",
			),
			"sr-ec" => array(
				"value" => "Rome",
				"language" => "en",
				"source" => "en",
			),
		);
		$expectedSerialization = array(
			array(
				"language" => "en",
				"value" => "Rome"
			),
			array(
				"language" => "de",
				"for-language" => "de-formal",
				"value" => "Rom"
			),
			array(
				"language" => "it",
				"removed" => ""
			),
			array(
				"language" => "fr",
				"removed" => ""
			),
			array(
				"language" => "zh-tw",
				"source-language" => "zh-cn",
				"value" => "羅馬"
			),
			array(
				"language" => "en",
				"source-language" => "en",
				"for-language" => "sr-ec",
				"value" => "Rome"
			),
		);
		$validArgs[] = array( $values, $options, $expectedSerialization );

		return $validArgs;
	}

	/**
	 * @dataProvider provideSerialize
	 */
	public function testSerialize( $values, $options, $expectedSerialization ) {
		$serializer = new MultilingualSerializer( $options );
		$serialized = $serializer->serializeMultilingualValues( $values );

		$this->assertEquals( $expectedSerialization, $serialized );
	}

	public function provideFilter() {
		$validArgs = array();

		$options = new MultiLangSerializationOptions();
		$options->setUseKeys( true );
		$options->setLanguages( array( 'en', 'it', 'de', 'fr' ) );
		$values = array(
			"en" => "capital city of Italy",
			"de" => "Hauptstadt von Italien",
			"it" => "",
			"fi" => "kunta Italiassa",
		);
		$expectedOutput = array(
			"en" => array(
				"value" => "capital city of Italy",
				"language" => "en",
				"source" => null,
			),
			"de" => array(
				"value" => "Hauptstadt von Italien",
				"language" => "de",
				"source" => null,
			),
			"it" => array(
				"value" => "",
				"language" => "it",
				"source" => null,
			),
		);
		$validArgs[] = array( $values, $options, $expectedOutput );

		$options = new MultiLangSerializationOptions();
		$languageFallbackChainFactory = new LanguageFallbackChainFactory();
		$options->setUseKeys( true );
		$options->setLanguages( array(
			'de-formal' => $languageFallbackChainFactory->newFromLanguageCode( 'de-formal' ),
			'zh-cn' => $languageFallbackChainFactory->newFromLanguageCode( 'zh-cn' ),
			'key-fr' => $languageFallbackChainFactory->newFromLanguageCode( 'fr' ),
			'sr-ec' => $languageFallbackChainFactory->newFromLanguageCode( 'zh-cn', LanguageFallbackChainFactory::FALLBACK_SELF ),
			'gan-hant' => $languageFallbackChainFactory->newFromLanguageCode( 'gan-hant' ),
	      	) );
		$values = array(
			"en" => "capital city of Italy",
			"de" => "Hauptstadt von Italien",
			"fi" => "kunta Italiassa",
			"zh-tw" => "羅馬",
			"gan-hant" => "羅馬G",
		);
		$expectedOutput = array(
			"de-formal" => array(
				"value" => "Hauptstadt von Italien",
				"language" => "de",
				"source" => null,
			),
			"zh-cn" => array(
				"value" => "罗马",
				"language" => "zh-cn",
				"source" => "zh-tw",
			),
			"key-fr" => array(
				"value" => "capital city of Italy",
				"language" => "en",
				"source" => null,
			),
			"gan-hant" => array(
				"value" => "羅馬G",
				"language" => "gan-hant",
				"source" => null,
			),
		);
		$validArgs[] = array( $values, $options, $expectedOutput );

		return $validArgs;
	}

	/**
	 * @dataProvider provideFilter
	 */
	public function testFilter( $values, $options, $expectedOutput ) {
		$serializer = new MultilingualSerializer( $options );
		$filtered = $serializer->filterPreferredMultilingualValues( $values );

		$this->assertEquals( $expectedOutput, $filtered );
	}

}
