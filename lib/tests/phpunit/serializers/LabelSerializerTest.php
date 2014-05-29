<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Serializers\LabelSerializer;
use Wikibase\Lib\Serializers\MultilingualSerializer;
use Wikibase\Lib\Serializers\SerializationOptions;

/**
 * @covers Wikibase\Lib\Serializers\LabelSerializer
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 * @group WikibaseLabelSerializer
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class LabelSerializerTest extends \PHPUnit_Framework_TestCase {

	public function validProvider() {
		$validArgs = array();

		$options = new SerializationOptions();
		$options->setIndexTags( false );
		$labels = array(
			"en" => "Rome",
			"de" => "Rom",
			"it" => "",
			"fi" => "Rooma",
		);
		$expectedSerialization = array(
			"en" => array(
				"language" => "en",
				"value" => "Rome"
			),
			"de" => array(
				"language" => "de",
				"value" => "Rom"
			),
			"it" => array(
				"language" => "it",
				"removed" => ""
			),
			"fi" => array(
				"language" => "fi",
				"value" => "Rooma"
			),
		);
		$validArgs[] = array( $labels, $options, $expectedSerialization );

		$options = new SerializationOptions();
		$options->setIndexTags( true );
		$labels = array(
			"en" => "Rome",
			"de" => "Rom",
			"it" => "Roma",
			"fi" => "Rooma",
		);
		$expectedSerialization = array(
			array(
				"language" => "en",
				"value" => "Rome"
			),
			array(
				"language" => "de",
				"value" => "Rom"
			),
			array(
				"language" => "it",
				"value" => "Roma"
			),
			array(
				"language" => "fi",
				"value" => "Rooma"
			),
			"_element" => "label",
		);
		$validArgs[] = array( $labels, $options, $expectedSerialization );

		$options = new SerializationOptions();
		$options->setIndexTags( false );
		$labels = array(
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
		$validArgs[] = array( $labels, $options, $expectedSerialization );

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
			"_element" => "label",
		);
		$validArgs[] = array( $descriptions, $options, $expectedSerialization );

		return $validArgs;
	}

	/**
	 * @dataProvider validProvider
	 */
	public function testGetSerialized( $labels, $options, $expectedSerialization ) {
		$labelSerializer = new LabelSerializer( $options );
		$serializedLabels = $labelSerializer->getSerialized( $labels );

		$this->assertEquals( $expectedSerialization, $serializedLabels );
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
	public function testInvalidGetSerialized( $labels ) {
		$labelSerializer = new LabelSerializer();
		$labelSerializer->getSerialized( $labels );
	}

	/**
	 * @dataProvider provideGetSerializedMultilingualValues
	 */
	public function testGetSerializedMultilingualValues( $values, $options ) {
		$multilingualSerializer = new MultilingualSerializer( $options );
		$labelSerializer = new LabelSerializer( $options, $multilingualSerializer );
		$filtered = $multilingualSerializer->filterPreferredMultilingualValues( $values );
		$expected = $labelSerializer->getSerialized( $filtered );

		$this->assertEquals( $expected, $labelSerializer->getSerializedMultilingualValues( $values ) );
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
		$labelSerializer = new LabelSerializer( new SerializationOptions() );

		$labels = $labelSerializer->newFromSerialization( $serialized );
		$this->assertEquals( $expected, $labels, $message );
	}

	public function newFromSerializationProvider() {
		$options = new SerializationOptions();
		$options->setIndexTags( true );
		$labelSerializer = new LabelSerializer( $options );

		$labels = array(
			"en" => "Rome",
			"de" => "Rom",
			"it" => "Roma"
		);

		$serialized = $labelSerializer->getSerialized( $labels );

		$options->setIndexTags( false );
		$labelSerializer = new LabelSerializer( $options );

		$serialized2 = $labelSerializer->getSerialized( $labels );

		return array(
			array( $labels, $serialized, 'serialization with index tags' ),
			array( $labels, $serialized2, 'serialization without index tags' )
		);
	}
}
