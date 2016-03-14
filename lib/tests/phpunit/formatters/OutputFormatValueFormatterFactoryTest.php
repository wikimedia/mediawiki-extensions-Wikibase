<?php

namespace Wikibase\Lib\Test;

use DataValues\DataValue;
use DataValues\StringValue;
use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\FormattingException;
use ValueFormatters\StringFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\PlainEntityIdFormatter;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\EntityIdValueFormatter;
use Wikibase\Lib\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\OutputFormatValueFormatterFactory;
use Wikibase\Lib\SnakFormatter;

/**
 * @covers Wikibase\Lib\OutputFormatValueFormatterFactory
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class OutputFormatValueFormatterFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constructorErrorsProvider
	 */
	public function testConstructorErrors( $builder, $error ) {
		$language = Language::factory( 'en' );
		$this->setExpectedException( $error );
		new OutputFormatValueFormatterFactory( $builder, $language, new LanguageFallbackChainFactory() );
	}

	public function constructorErrorsProvider() {
		$stringFormatter = new StringFormatter( new FormatterOptions() );

		return array(
			'keys must be strings' => array(
				array( 17 => $stringFormatter ),
				'InvalidArgumentException'
			),
			'builder must be callable' => array(
				array( 'foo' => 17 ),
				'InvalidArgumentException'
			),
		);
	}

	private function newOutputFormatValueFormatterFactory() {
		$factoryCallbacks = array(
			'VT:string' => function( $format, FormatterOptions $options ) {
				return new StringFormatter();
			},
			'PT:url' => function( $format, FormatterOptions $options ) {
				return new StringFormatter();
			},
		);

		return new OutputFormatValueFormatterFactory(
			$factoryCallbacks,
			Language::factory( 'en' ),
			new LanguageFallbackChainFactory()
		);
	}

	/**
	 * @dataProvider provideGetValueFormatter
	 */
	public function testGetValueFormatter( $format, DataValue $value, $datatype, $expectedPattern ) {
		$factory = $this->newOutputFormatValueFormatterFactory();
		$formatter = $factory->getValueFormatter( $format, new FormatterOptions() );

		$this->assertInstanceOf( 'Wikibase\Lib\DispatchingValueFormatter', $formatter );

		// assert that the formatter we got conforms to the expected behavior
		$actual = $formatter->formatValue( $value, $datatype );
		$this->assertRegExp( $expectedPattern, $actual );
	}

	public function provideGetValueFormatter() {
		return array(
			'plain string' => array(
				SnakFormatter::FORMAT_PLAIN,
				new StringValue( '{foo&bar}' ),
				null,
				'/^{foo&bar}$/'
			),
			'wikitext url' => array(
				SnakFormatter::FORMAT_WIKI,
				new StringValue( 'http://acme.com/?foo&bar' ),
				'url',
				'!^http://acme.com/\?foo&bar$!'
			),
		);
	}

	/**
	 * @dataProvider provideApplyLanguageDefaults
	 */
	public function testApplyLanguageDefaults( FormatterOptions $options, $expectedLanguage, $expectedFallback ) {
		$factory = $this->newOutputFormatValueFormatterFactory();

		$factory->applyLanguageDefaults( $options );

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

	public function provideApplyLanguageDefaults() {
		$languageFallbackFactory = new LanguageFallbackChainFactory();
		$languageFallback = $languageFallbackFactory->newFromLanguage( Language::factory( 'fr' ) );

		return array(
			'empty' => array(
				new FormatterOptions( array() ),
				'en', // determined in OutputFormatValueFormatterFactoryTest::newBuilder()
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

	public function testSetFormatterFactoryCallback() {
		$factory = $this->newOutputFormatValueFormatterFactory();
		$factory->setFormatterFactoryCallback(
			'VT:wikibase-entityid',
			function () {
				return new EntityIdValueFormatter( new PlainEntityIdFormatter() );
			}
		);

		$factory->setFormatterFactoryCallback(
			'VT:string',
			null
		);

		$formatter = $factory->getValueFormatter( SnakFormatter::FORMAT_PLAIN, new FormatterOptions() );

		$this->assertEquals(
			'Q5',
			$formatter->formatValue( new EntityIdValue( new ItemId( "Q5" ) ) ),
			'Extra formatter'
		);

		// formatter for 'VT:string' should have been removed
		$this->setExpectedException( FormattingException::class );
		$formatter->format( new StringValue( 'boo!' ) ); // expecting a FormattingException
	}

}
