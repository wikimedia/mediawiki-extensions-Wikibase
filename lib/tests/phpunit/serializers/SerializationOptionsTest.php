<?php

namespace Wikibase\Test;

use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\MultiLangSerializationOptions;
use Wikibase\LanguageFallbackChainFactory;

/**
 * @covers  Wikibase\Lib\Serializers\SerializationOptions
 *
 * @since 0.2
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Liangent < liangent@gmail.com >
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

		$factory = new LanguageFallbackChainFactory();

		foreach ( $languages as $languageKey => &$languageValue ) {
			if ( !is_numeric( $languageKey ) ) {
				$languageValue = $factory->newFromLanguageCode( $languageKey, $languageValue );
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
			array( array( 'en' ), array( 'en' ), array( 'en' => LanguageFallbackChainFactory::FALLBACK_SELF ) ),
			array( array( 'en', 'de' ), array( 'en', 'de' ), array(
				'en' => LanguageFallbackChainFactory::FALLBACK_SELF, 'de' => LanguageFallbackChainFactory::FALLBACK_SELF
			) ),
			array(
				array( 'en', 'zh' => LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS ),
				array( 'en', 'zh' ),
				array(
					'en' => LanguageFallbackChainFactory::FALLBACK_SELF,
					'zh' => LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				),
			),
			array(
				array(
					'de-formal' => LanguageFallbackChainFactory::FALLBACK_OTHERS,
					'sr' => LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				),
				array( 'de-formal', 'sr' ),
				array(
					'de-formal' => LanguageFallbackChainFactory::FALLBACK_OTHERS,
					'sr' => LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				),
			),
		);
	}

	/**
	 * @dataProvider provideIdKeyMode
	 */
	public function testSetIdKeyMode( $mode ) {
		$options = new SerializationOptions();
		$options->setIdKeyMode( $mode );

		$this->assertEquals( $mode & SerializationOptions::ID_KEYS_LOWER, $options->shouldUseLowerCaseIdsAsKeys() );
		$this->assertEquals( $mode & SerializationOptions::ID_KEYS_UPPER, $options->shouldUseUpperCaseIdsAsKeys() );
	}

	public function provideIdKeyMode() {
		return array(
			'lower' => array( SerializationOptions::ID_KEYS_LOWER ),
			'upper' => array( SerializationOptions::ID_KEYS_UPPER ),
			'both' => array( SerializationOptions::ID_KEYS_BOTH ),
		);
	}

	/**
	 * @dataProvider provideBadIdKeyMode
	 */
	public function testBadSetIdKeyMode( $mode ) {
		$this->setExpectedException( '\InvalidArgumentException' );

		$options = new SerializationOptions();
		$options->setIdKeyMode( $mode );
	}

	public function provideBadIdKeyMode() {
		return array(
			'none' => array( 0 ),
			'badr' => array( 17 ),
		);
	}

}
