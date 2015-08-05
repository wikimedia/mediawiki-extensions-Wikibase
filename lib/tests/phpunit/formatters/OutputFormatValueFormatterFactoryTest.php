<?php

namespace Wikibase\Lib\Test;

use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
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
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class OutputFormatValueFormatterFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constructorErrorsProvider
	 */
	public function testConstructorErrors( $builder, $error ) {
		$language = Language::factory( 'en' );
		$this->setExpectedException( $error );
		new OutputFormatValueFormatterFactory( $builder, $language );
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

	public function makeMockValueFormatter( $value ) {
		$mock = $this->getMock( 'ValueFormatters\ValueFormatter' );

		$mock->expects( $this->atLeastOnce() )
			->method( 'format' )
			->will( $this->returnValue( $value ) );

		return $mock;
	}

	/**
	 * @dataProvider getValueFormatterProvider
	 */
	public function testGetValueFormatter( $builders, $format ) {
		$language = Language::factory( 'en' );
		$factory = new OutputFormatValueFormatterFactory( $builders, $language );
		$formatter = $factory->getValueFormatter( $format, new FormatterOptions() );

		$this->assertInstanceOf( 'ValueFormatters\ValueFormatter', $formatter );
	}

	public function getValueFormatterProvider() {
		$self = $this;
		$builders = array(
			'VT:foo' => function() use ( $self ) {
				return $self->makeMockValueFormatter( '<FOO>' );
			},
			'VT:bar' => function() use ( $self ) {
				return $self->makeMockValueFormatter( '<BAR>' );
			},
		);

		return array(
			'foo/plain' => array(
				$builders,
				SnakFormatter::FORMAT_PLAIN
			),
			'bar/html' => array(
				$builders,
				SnakFormatter::FORMAT_HTML
			),
		);
	}


	/**
	 * @dataProvider applyLanguageDefaultsProvider
	 */
	public function testApplyLanguageDefaults( FormatterOptions $options, $expectedLanguage, $expectedFallback ) {
		$builders = $this->newWikibaseValueFormatterBuilders();

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
				'en', // determined in WikibaseValueFormatterBuildersTest::newBuilder()
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

	public function testSetValueFormatterBuilder() {
		$builder = function () {
			return new EntityIdValueFormatter( new PlainEntityIdFormatter() );
		};

		$builders = $this->newWikibaseValueFormatterBuilders();
		$builders->setValueFormatterBuilder(
			SnakFormatter::FORMAT_PLAIN,
			'VT:wikibase-entityid',
			$builder
		);
		$builders->setValueFormatterBuilder(
			SnakFormatter::FORMAT_PLAIN,
			'VT:time',
			null
		);

		$options = new FormatterOptions();
		$factory = new OutputFormatValueFormatterFactory(
			$builders->getValueFormatterBuildersForFormats()
		);
		$formatter = $builders->buildDispatchingValueFormatter(
			$factory,
			SnakFormatter::FORMAT_PLAIN,
			$options
		);

		$this->assertEquals(
			'Q5',
			$formatter->format( new EntityIdValue( new ItemId( "Q5" ) ) ),
			'Extra formatter'
		);

		$this->setExpectedException( 'ValueFormatters\FormattingException' );

		$timeValue = new TimeValue(
			'+2013-01-01T00:00:00Z',
			0, 0, 0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php'
		);
		$formatter->format( $timeValue ); // expecting a FormattingException
	}

	public function testFormatFallback() {
		$this->fail( 'TODO' );
	}
}
