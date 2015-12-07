<?php

namespace Wikibase\Lib\Test;

use DataValues\DataValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use Language;
use MediaWikiTestCase;
use Title;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Term\Term;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\WikibaseValueFormatterBuilders;

/**
 * @covers Wikibase\Lib\WikibaseValueFormatterBuilders
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
class WikibaseValueFormatterBuildersTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( 'wgArticlePath', '/wiki/$1' );
	}

	/**
	 * @param EntityTitleLookup|null $entityTitleLookup
	 *
	 * @return WikibaseValueFormatterBuilders
	 */
	private function newWikibaseValueFormatterBuilders( EntityTitleLookup $entityTitleLookup = null ) {
		$termLookup = $this->getMock( 'Wikibase\DataModel\Services\Lookup\TermLookup' );

		$termLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnCallback( function ( EntityId $id, $language ) {
				switch ( $language ) {
					case 'de':
						return 'Name für ' . $id->getSerialization();
					default:
						return 'Label for ' . $id->getSerialization();
				}
			} ) );

		$termLookup->expects( $this->any() )
			->method( 'getLabels' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return array(
					'de' => 'Name für ' . $id->getSerialization(),
					'en' => 'Label for ' . $id->getSerialization(),
				);
			} ) );

		$languageNameLookup = $this->getMock( 'Wikibase\Lib\LanguageNameLookup' );
		$languageNameLookup->expects( $this->any() )
			->method( 'getName' )
			->will( $this->returnValue( 'Deutsch' ) );

		return new WikibaseValueFormatterBuilders(
			Language::factory( 'en' ),
			new FormatterLabelDescriptionLookupFactory( $termLookup ),
			$languageNameLookup,
			new BasicEntityIdParser(),
			$entityTitleLookup
		);
	}

	private function newFormatterOptions( $lang = 'en' ) {
		$fallbackMode = LanguageFallbackChainFactory::FALLBACK_ALL;
		$fallbackChainFactory = new LanguageFallbackChainFactory();
		$fallbackChain = $fallbackChainFactory->newFromLanguageCode( $lang, $fallbackMode );

		return new FormatterOptions( array(
			ValueFormatter::OPT_LANG => $lang,
			FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $fallbackChain,
		) );
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getTitleLookup() {
		$titleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );

		$titleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback(
				function ( EntityId $id ) {
					return Title::makeTitle( NS_MAIN, $id->getSerialization() );
				}
			) );

		return $titleLookup;
	}

	/**
	 * @param string $type
	 * @param string $format
	 * @param FormatterOptions $options
	 *
	 * @return mixed
	 */
	private function getFormatterForDataType( $type, $format, $options ) {
		$builders = $this->newWikibaseValueFormatterBuilders(
			$this->getTitleLookup()
		);

		$factory = array( $builders, 'new' . $type . 'Formatter' );
		$formatter = call_user_func( $factory, $format, $options );

		$this->assertInstanceOf( 'ValueFormatters\ValueFormatter', $formatter );
		return $formatter;
	}

	/**
	 * @dataProvider provideNewFormatter
	 */
	public function testNewFormatter(
		$type,
		$format,
		FormatterOptions $options,
		DataValue $value,
		$expected
	) {
		$formatter = $this->getFormatterForDataType( $type, $format, $options );

		$text = $formatter->format( $value );
		$this->assertRegExp( $expected, $text );
	}

	public function provideNewFormatter() {
		return array(
			// Url
			'plain url' => array(
				'Url',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions(),
				new StringValue( 'http://acme.com/' ),
				'@^http://acme\\.com/$@'
			),
			'wikitext url' => array(
				'Url',
				SnakFormatter::FORMAT_WIKI,
				$this->newFormatterOptions(),
				new StringValue( 'http://acme.com/' ),
				'@^http://acme\\.com/$@'
			),
			'html url' => array(
				'Url',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new StringValue( 'http://acme.com/' ),
				'@^.*href="http://acme.com/".*$@'
			),

			// EntityId
			'plain item label (with language fallback)' => array(
				'EntityId',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'de-ch' ), // should fall back to 'de'
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'@^Name für Q5$@' // compare mock object created in newBuilders()
			),
			'item link (with entity lookup)' => array(
				'EntityId',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'/^<a\b[^>]* href="[^"]*\bQ5">Label for Q5<\/a>.*$/', // compare mock object created in newBuilders()
				'wikibase-item'
			),
			'property link (with entity lookup)' => array(
				'EntityId',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new EntityIdValue( new PropertyId( 'P5' ) ),
				'/^<a\b[^>]* href="[^"]*\bP5">Label for P5<\/a>.*$/',
				'wikibase-property'
			),

			// CommonsMedia
			'plain commons media' => array(
				'CommonsMedia',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions(),
				new StringValue( 'Example.jpg' ),
				'@^Example.jpg$@',
			),
			'html commons link' => array(
				'CommonsMedia',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new StringValue( 'Example.jpg' ),
				'@^<a class="extiw" href="//commons\\.wikimedia\\.org/wiki/File:Example\\.jpg">Example\\.jpg</a>$@',
				'commonsMedia'
			),

			// GlobeCoordinate
			'plain coordinate' => array(
				'GlobeCoordinate',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions(),
				new GlobeCoordinateValue( new LatLongValue( -55.755786, 37.25633 ), 0.25 ),
				'@^55°45\'S, 37°15\'E$@'
			),
			'coordinate details' => array(
				'GlobeCoordinate',
				SnakFormatter::FORMAT_HTML_DIFF,
				$this->newFormatterOptions( 'de' ),
				new GlobeCoordinateValue( new LatLongValue( -55.755786, 37.25633 ), 0.25 ),
				'@^.*55° 45\', 37° 15\'.*$@'
			),

			// Quantity
			'localized quantity' => array(
				'Quantity',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'de' ),
				QuantityValue::newFromNumber( '+123456.789' ),
				'@^123\\.456,789$@'
			),
			'quantity details' => array(
				'Quantity',
				SnakFormatter::FORMAT_HTML_DIFF,
				$this->newFormatterOptions( 'de' ),
				QuantityValue::newFromNumber( '+123456.789' ),
				'@^.*123\\.456,789.*$@'
			),

			// Time
			'a month in 1980' => array(
				'Time',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions(),
				new TimeValue(
					'+1980-05-01T00:00:00Z',
					0, 0, 0,
					TimeValue::PRECISION_MONTH,
					'http://www.wikidata.org/entity/Q1985727'
				),
				'/^May 1980$/'
			),
			'a gregorian day in 1520' => array(
				'Time',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new TimeValue(
					'+1520-05-01T00:00:00Z',
					0, 0, 0,
					TimeValue::PRECISION_DAY,
					'http://www.wikidata.org/entity/Q1985727'
				),
				'/^1 May 1520<sup class="wb-calendar-name">Gregorian<\/sup>$/'
			),
			'a julian day in 1980' => array(
				'Time',
				SnakFormatter::FORMAT_HTML_DIFF,
				$this->newFormatterOptions(),
				new TimeValue(
					'+1980-05-01T00:00:00Z',
					0, 0, 0,
					TimeValue::PRECISION_DAY,
					'http://www.wikidata.org/entity/Q1985786'
				),
				'/^.*>1 May 1980<sup class="wb-calendar-name">Julian<\/sup>.*$/'
			),

			// Monolingual
			'text in english' => array(
				'Monolingual',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'en' ),
				new MonolingualTextValue( 'en', 'Hello World' ),
				'/^Hello World$/'
			),
			'text in german' => array(
				'Monolingual',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions( 'en' ),
				new MonolingualTextValue( 'de', 'Hallo Welt' ),
				'/^.*lang="de".*?>Hallo Welt<.*Deutsch.*$/'
			),
		);
	}

	/**
	 * In case WikibaseValueFormatterBuilders doesn't have a EntityTitleLookup it returns
	 * a formatter which doesn't link the entity id.
	 *
	 * @dataProvider provideNewFormatter_noTitleLookup
	 */
	public function testNewFormatter_noTitleLookup(
		$name,
		$format,
		FormatterOptions $options,
		DataValue $value,
		$expected
	) {
		$formatter = $this->getFormatterForDataType( $name, $format, $options );

		$text = $formatter->format( $value );
		$this->assertRegExp( $expected, $text );
	}

	public function provideNewFormatter_noTitleLookup() {
		return array(
			'plain item label' => array(
				'EntityId',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions(),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'@^Label for Q5$@'
			),
			'item link' => array(
				'EntityId',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'/^.*Label for Q5.*$/'
			)
		);
	}

	/**
	 * @dataProvider provideNewFormatter_LabelDescriptionLookupOption
	 */
	public function testNewFormatter_LabelDescriptionLookupOption(
		$name,
		FormatterOptions $options,
		DataValue $value,
		$expected
	) {
		$formatter = $this->getFormatterForDataType( $name, SnakFormatter::FORMAT_HTML, $options );

		$text = $formatter->format( $value );
		$this->assertRegExp( $expected, $text );
	}

	public function provideNewFormatter_LabelDescriptionLookupOption() {
		$labelDescriptionLookup = $this->getMock( 'Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup' );
		$labelDescriptionLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( new Term( 'xy', 'Custom LabelDescriptionLookup' ) ) );

		$fallbackFactory = new LanguageFallbackChainFactory();
		$fallbackChain = $fallbackFactory->newFromLanguage( Language::factory( 'de-ch' ) );

		return array(
			'language option' => array(
				'EntityId',
				new FormatterOptions( array(
					ValueFormatter::OPT_LANG => 'de',
				) ),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'@>Name für Q5<@'
			),
			'fallback option' => array(
				'EntityId',
				new FormatterOptions( array(
					FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $fallbackChain,
				) ),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'@>Name für Q5<@'
			),
			'LabelDescriptionLookup option' => array(
				'EntityId',
				new FormatterOptions( array(
					FormatterLabelDescriptionLookupFactory::OPT_LABEL_DESCRIPTION_LOOKUP => $labelDescriptionLookup,
				) ),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'@>Custom LabelDescriptionLookup<@'
			),
		);
	}

}
