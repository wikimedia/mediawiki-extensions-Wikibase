<?php

namespace Wikibase\Lib\Test;

use DataValues\DataValue;
use DataValues\StringValue;
use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\FormatterLabelDescriptionLookupFactory;

/**
 * @covers Wikibase\Lib\ValueFormatterRegistry
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ValueFormatterRegistryTest extends \PHPUnit_Framework_TestCase {

	public function testGetValueFormatters() {
		$this->fail( 'TODO' );
	}

	public function testGetPlainTextFormatters() {
		$this->fail( 'TODO' );
	}

	public function testGetHtmlFormatters() {
		$this->fail( 'TODO' );
	}

	public function testGetWikiTextFormatters() {
		$this->fail( 'TODO' );
	}

	public function testGetWidgetFormatters() {
		$this->fail( 'TODO' );
	}

	public function testGetDiffFormatters() {
		$this->fail( 'TODO' );
	}

	public function testMakeEscapingFormatters() {
		$this->fail( 'TODO' );
	}

	/**
	 * @dataProvider applyLanguageDefaultsProvider
	 */
	public function testApplyLanguageDefaults( FormatterOptions $options, $expectedLanguage, $expectedFallback ) {
		$builders = $this->newValueFormatterRegistry();

		$builders->applyLanguageDefaults( $options );

		if ( $expectedLanguage !== null ) {
			$lang = $options->getOption( ValueFormatter::OPT_LANG );
			$this->assertEquals( $expectedLanguage, $lang, 'OPT_LANG' );
		}

		if ( $expectedFallback !== null ) {
			/** @var LanguageFallbackChain $languageFallback */
			$languageFallback = $options->getOption( FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN );
			$languages = $languageFallback->getFallbackChain();
			$lang = $languages[0]->getLanguage()->getCode();

			$this->assertEquals( $expectedFallback, $lang, 'OPT_LANGUAGE_FALLBACK_CHAIN' );
		}
	}

	public function applyLanguageDefaultsProvider() {
		$languageFallbackFactory = new LanguageFallbackChainFactory();
		$languageFallback = $languageFallbackFactory->newFromLanguage( Language::factory( 'fr' ) );

		return array(
			'empty' => array(
				new FormatterOptions( array() ),
				'en', // determined in ValueFormatterRegistryTest::newBuilder()
				'en'  // derived from language code
			),
			'language code set' => array(
				new FormatterOptions( array( ValueFormatter::OPT_LANG => 'de' ) ),
				'de', // as given
				'de'  // derived from language code
			),
			'language fallback set' => array(
				new FormatterOptions( array(
					FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $languageFallback
				) ),
				'en', // default code is taken from the constructor, not the fallback chain
				'fr'  // as given
			),
			'language code and fallback set' => array(
				new FormatterOptions( array(
					ValueFormatter::OPT_LANG => 'de',
					FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $languageFallback
				) ),
				'de', // as given
				'fr'  // as given
			),
		);
	}

}
