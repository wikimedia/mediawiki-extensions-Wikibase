<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Serializers\DescriptionSerializer;
use Wikibase\Lib\Serializers\MultilingualSerializer;
use Wikibase\Lib\Serializers\SerializationOptions;

/**
 * @covers Wikibase\Lib\Serializers\DescriptionSerializer
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 * @group WikibaseDescriptionSerializer
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class DescriptionSerializerTest extends \PHPUnit_Framework_TestCase {

	public function validProvider() {
		$validArgs = array();

		$options = new SerializationOptions();
		$options->setIndexTags( false );
		$descriptions = array(
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
		$validArgs[] = array( $descriptions, $options, $expectedSerialization );

		$options = new SerializationOptions();
		$options->setIndexTags( true );
		$descriptions = array(
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
			"_element" => "description",
		);
		$validArgs[] = array( $descriptions, $options, $expectedSerialization );

		$options = new SerializationOptions();
		$options->setIndexTags( false );
		$descriptions = array(
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
		$validArgs[] = array( $descriptions, $options, $expectedSerialization );

		$options = new SerializationOptions();
		$options->setIndexTags( true );
		$descriptions = array(
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
			"_element" => "description",
		);
		$validArgs[] = array( $descriptions, $options, $expectedSerialization );

		return $validArgs;
	}

	/**
	 * @dataProvider validProvider
	 */
	public function testGetSerialized( $descriptions, $options, $expectedSerialization ) {
		$descriptionSerializer = new DescriptionSerializer( $options );
		$serializedDescriptions = $descriptionSerializer->getSerialized( $descriptions );

		$this->assertEquals( $expectedSerialization, $serializedDescriptions );
	}

	public function invalidProvider() {
		$invalidArgs = array();

		$invalidArgs[] = array( 'foo' );
		$invalidArgs[] = array( 42 );

		return $invalidArgs;
	}

	/**
	 * @dataProvider invalidProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidGetSerialized( $descriptions ) {
		$descriptionSerializer = new DescriptionSerializer();
		$descriptionSerializer->getSerialized( $descriptions );
	}

	/**
	 * @dataProvider provideGetSerializedMultilingualValues
	 */
	public function testGetSerializedMultilingualValues( $values, $options ) {
		$multilingualSerializer = new MultilingualSerializer( $options );
		$descriptionSerializer = new DescriptionSerializer( $options, $multilingualSerializer );
		$filtered = $multilingualSerializer->filterPreferredMultilingualValues( $values );
		$expected = $descriptionSerializer->getSerialized( $filtered );

		$this->assertEquals( $expected, $descriptionSerializer->getSerializedMultilingualValues( $values ) );
	}

	public function provideGetSerializedMultilingualValues() {
		$validArgs = array();

		$options = new SerializationOptions();
		$options->setIndexTags( false );
		$options->setLanguages( array( 'en', 'it', 'de', 'fr' ) );
		$values = array(
			"en" => "capital city of Italy",
			"de" => "Hauptstadt von Italien",
			"it" => "",
			"fi" => "kunta Italiassa",
		);
		$validArgs[] = array( $values, $options );

		$options = new SerializationOptions();
		$languageFallbackChainFactory = new LanguageFallbackChainFactory();
		$options->setIndexTags( false );
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
		$validArgs[] = array( $values, $options );

		return $validArgs;
	}

	/**
	 * @dataProvider newFromSerializationProvider
	 */
	public function testNewFromSerialization( $expected, $serialized, $message ) {
		$descriptionSerializer = new DescriptionSerializer( new SerializationOptions() );

		$descriptions = $descriptionSerializer->newFromSerialization( $serialized );
		$this->assertEquals( $expected, $descriptions, $message );
	}

	public function newFromSerializationProvider() {
		$options = new SerializationOptions();
		$options->setIndexTags( true );

		$descriptionSerializer = new DescriptionSerializer( $options );

		$descriptions = array(
			"en" => "capital city of Italy",
			"de" => "Hauptstadt von Italien",
			"fi" => "kunta Italiassa"
		);

		$serialized = $descriptionSerializer->getSerialized( $descriptions );

		$options->setIndexTags( false );
		$descriptionSerializer = new DescriptionSerializer( $options );
		$serialized2 = $descriptionSerializer->getSerialized( $descriptions );

		return array(
			array( $descriptions, $serialized, 'serialization with index tags' ),
			array( $descriptions, $serialized2, 'serialization without index tags' )
		);
	}
}
