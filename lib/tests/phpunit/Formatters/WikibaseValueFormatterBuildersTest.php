<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\DataValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use DataValues\UnboundedQuantityValue;
use DataValues\UnDeserializableValue;
use Language;
use MediaWikiTestCase;
use Psr\SimpleCache\CacheInterface;
use Title;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\WikibaseValueFormatterBuilders;

/**
 * @covers Wikibase\Lib\WikibaseValueFormatterBuilders
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikibaseValueFormatterBuildersTest extends MediaWikiTestCase {

	const GEO_SHAPE_STORAGE_FRONTEND_URL = '//commons.wikimedia.org/wiki/';

	const TABULAR_DATA_STORAGE_FRONTEND_URL = '//commons2.wikimedia.org/wiki/';

	const CACHE_TTL_IN_SECONDS = 10;

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( 'wgArticlePath', '/wiki/$1' );
	}

	/**
	 * @param EntityTitleLookup $entityTitleLookup
	 *
	 * @return WikibaseValueFormatterBuilders
	 */
	private function newWikibaseValueFormatterBuilders( EntityTitleLookup $entityTitleLookup ) {
		$termLookup = $this->getMock( TermLookup::class );

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
				return [
					'de' => 'Name für ' . $id->getSerialization(),
					'en' => 'Label for ' . $id->getSerialization(),
				];
			} ) );

		$languageNameLookup = $this->getMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->any() )
			->method( 'getName' )
			->will( $this->returnValue( 'Deutsch' ) );

		return new WikibaseValueFormatterBuilders(
			Language::factory( 'en' ),
			new FormatterLabelDescriptionLookupFactory( $termLookup ),
			$languageNameLookup,
			new ItemIdParser(),
			self::GEO_SHAPE_STORAGE_FRONTEND_URL,
			self::TABULAR_DATA_STORAGE_FRONTEND_URL,
			$this->createCache(),
			self::CACHE_TTL_IN_SECONDS,
			$this->createMock( EntityLookup::class ),
			$this->createMock( EntityRevisionLookup::class ),
			$entityTitleLookup
		);
	}

	private function newFormatterOptions( $lang = 'en' ) {
		$fallbackMode = LanguageFallbackChainFactory::FALLBACK_ALL;
		$fallbackChainFactory = new LanguageFallbackChainFactory();
		$fallbackChain = $fallbackChainFactory->newFromLanguageCode( $lang, $fallbackMode );

		return new FormatterOptions( [
			ValueFormatter::OPT_LANG => $lang,
			FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $fallbackChain,
		] );
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getTitleLookup() {
		$titleLookup = $this->getMock( EntityTitleLookup::class );

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
	 * @param string $functionName The factory function to call
	 * @param string $format
	 * @param FormatterOptions $options
	 *
	 * @return mixed
	 */
	private function callFactoryFunction( $functionName, $format, FormatterOptions $options ) {
		$builders = $this->newWikibaseValueFormatterBuilders(
			$this->getTitleLookup()
		);

		$factory = [ $builders, $functionName ];
		$formatter = call_user_func( $factory, $format, $options );

		$this->assertInstanceOf( ValueFormatter::class, $formatter );
		return $formatter;
	}

	public function testNewFormatter_formats() {
		$formats = [
			SnakFormatter::FORMAT_PLAIN,
			SnakFormatter::FORMAT_WIKI,
			SnakFormatter::FORMAT_HTML,
			SnakFormatter::FORMAT_HTML_DIFF,
		];

		$functionNames = [
			'newStringFormatter',
			'newUrlFormatter',
			'newCommonsMediaFormatter',
			'newGeoShapeFormatter',
			'newTabularDataFormatter',
			'newEntityIdFormatter',
			'newMonolingualFormatter',
			'newTimeFormatter',
			'newGlobeCoordinateFormatter',
			'newQuantityFormatter',
		];

		$options = new FormatterOptions();

		foreach ( $formats as $format ) {
			foreach ( $functionNames as $function ) {
				// callFactoryFunction asserts that a valid formatter is returned
				$this->callFactoryFunction( $function, $format, $options );
			}
		}
	}

	/**
	 * @dataProvider provideNewFormatter
	 */
	public function testNewFormatter(
		$formatterName,
		$format,
		FormatterOptions $options,
		DataValue $value,
		$expected
	) {
		$functionName = 'new' . ucfirst( $formatterName ) . 'Formatter';
		$formatter = $this->callFactoryFunction( $functionName, $format, $options );

		$text = $formatter->format( $value );
		$this->assertRegExp( $expected, $text );
	}

	public function provideNewFormatter() {
		return [
			// String
			'plain string' => [
				'String',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions(),
				new StringValue( 'foo bar' ),
				'@^foo bar$@'
			],
			'wikitext string' => [
				'String',
				SnakFormatter::FORMAT_WIKI,
				$this->newFormatterOptions(),
				new StringValue( 'foo[bar]' ),
				'@^foo&#91;bar&#93;$@'
			],
			'html string' => [
				'String',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new StringValue( 'foo<bar>' ),
				'@^foo&lt;bar&gt;$@'
			],

			// UnDeserializableValue
			'plain bad value' => [
				'UnDeserializableValue',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions(),
				new UnDeserializableValue( 'foo bar', 'xyzzy', 'broken' ),
				'@invalid@'
			],

			// Url
			'plain url' => [
				'Url',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions(),
				new StringValue( 'http://acme.com/' ),
				'@^http://acme\.com/$@'
			],
			'wikitext url' => [
				'Url',
				SnakFormatter::FORMAT_WIKI,
				$this->newFormatterOptions(),
				new StringValue( 'http://acme.com/' ),
				'@^http://acme\.com/$@'
			],
			'html url' => [
				'Url',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new StringValue( 'http://acme.com/' ),
				'@^.*href="http://acme.com/".*$@'
			],

			// EntityId
			'plain item label (with language fallback)' => [
				'EntityId',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'de-ch' ), // should fall back to 'de'
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'@^Name für Q5$@' // compare mock object created in newBuilders()
			],
			'item link (with entity lookup)' => [
				'EntityId',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'/^<a\b[^>]* href="[^"]*\bQ5">Label for Q5<\/a>.*$/', // compare mock object created in newBuilders()
			],
			'property link (with entity lookup)' => [
				'EntityId',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new EntityIdValue( new PropertyId( 'P5' ) ),
				'/^<a\b[^>]* href="[^"]*\bP5">Label for P5<\/a>.*$/',
			],

			// CommonsMedia
			'plain commons media' => [
				'CommonsMedia',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions(),
				new StringValue( 'Example.jpg' ),
				'@^Example.jpg$@',
			],
			'html commons link' => [
				'CommonsMedia',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new StringValue( 'Example.jpg' ),
				'@^<a class="extiw" href="//commons\.wikimedia\.org/wiki/File:Example\.jpg">Example\.jpg</a>$@',
			],
			'html commons inline image' => [
				'CommonsMedia',
				SnakFormatter::FORMAT_HTML_VERBOSE,
				$this->newFormatterOptions(),
				new StringValue( 'DOES-NOT-EXIST-dfsdf.jpg' ),
				'@^<div.*>.*<a.*href="https://commons\.wikimedia\.org/.*DOES-NOT-EXIST.*>.*</div>$@s',
			],
			// geo-shape
			'plain geo-shape' => [
				'GeoShape',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions(),
				new StringValue( 'Data:GeoShape.map' ),
				'@^Data:GeoShape.map$@',
			],
			'html geo-shape' => [
				'GeoShape',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new StringValue( 'Data:GeoShape.map' ),
				'@^<a class="extiw" href="//commons\.wikimedia\.org/wiki/Data:GeoShape\.map">Data:GeoShape\.map</a>$@',
			],
			'wikitext geo-shape' => [
				'GeoShape',
				SnakFormatter::FORMAT_WIKI,
				$this->newFormatterOptions(),
				new StringValue( 'Data:GeoShape.map' ),
				'@' .
				preg_quote(
					'[//commons.wikimedia.org/wiki/Data:GeoShape.map Data:GeoShape.map]',
					'@' ) .
				'@',
			],
			// tabular-data
			'plain tabular-data' => [
				'TabularData',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions(),
				new StringValue( 'Data:TabularData.tab' ),
				'@^Data:TabularData.tab$@',
			],
			'html tabular-data' => [
				'TabularData',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new StringValue( 'Data:TabularData.tab' ),
				'@^<a class="extiw" href="//commons2\.wikimedia\.org/wiki/Data:TabularData\.tab">Data:TabularData\.tab</a>$@',
			],
			'wikitext tabular-data' => [
				'TabularData',
				SnakFormatter::FORMAT_WIKI,
				$this->newFormatterOptions(),
				new StringValue( 'Data:TabularData.tab' ),
				'@' . preg_quote(
					'[//commons2.wikimedia.org/wiki/Data:TabularData.tab Data:TabularData.tab]',
					'@'
				) . '@',
			],
			// GlobeCoordinate
			'plain coordinate' => [
				'GlobeCoordinate',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions(),
				new GlobeCoordinateValue( new LatLongValue( -55.755786, 37.25633 ), 0.25 ),
				'@^55°45\'S, 37°15\'E$@'
			],
			'coordinate details' => [
				'GlobeCoordinate',
				SnakFormatter::FORMAT_HTML_DIFF,
				$this->newFormatterOptions( 'de' ),
				new GlobeCoordinateValue( new LatLongValue( -55.755786, 37.25633 ), 0.25 ),
				'@^.*55° 45\', 37° 15\'.*$@'
			],

			// Quantity
			'localized quantity' => [
				'Quantity',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'de' ),
				UnboundedQuantityValue::newFromNumber( '+123456.789' ),
				'@^123\.456,789$@'
			],
			'quantity details' => [
				'Quantity',
				SnakFormatter::FORMAT_HTML_DIFF,
				$this->newFormatterOptions( 'de' ),
				QuantityValue::newFromNumber( '+123456.789' ),
				'@^.*123\.456,789.*$@'
			],

			// Time
			'a month in 1980' => [
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
			],
			'a gregorian day in 1520' => [
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
			],
			'a julian day in 1980' => [
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
			],

			// Monolingual
			'text in english' => [
				'Monolingual',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'en' ),
				new MonolingualTextValue( 'en', 'Hello World' ),
				'/^Hello World$/'
			],
			'text in german' => [
				'Monolingual',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions( 'en' ),
				new MonolingualTextValue( 'de', 'Hallo Welt' ),
				'/ lang="de".*>Hallo Welt<.*Deutsch/'
			],
			'wikitext monolingual text' => [
				'Monolingual',
				SnakFormatter::FORMAT_WIKI,
				$this->newFormatterOptions( 'en' ),
				new MonolingualTextValue( 'de', 'Hallo Welt' ),
				'/ lang="de".*>Hallo Welt</'
			],
		];
	}

	/**
	 * In case WikibaseValueFormatterBuilders doesn't have a EntityTitleLookup it returns
	 * a formatter which doesn't link the entity id.
	 *
	 * @dataProvider provideNewFormatter_noTitleLookup
	 */
	public function testNewFormatter_noTitleLookup(
		$functionName,
		$format,
		FormatterOptions $options,
		DataValue $value,
		$expected
	) {
		$formatter = $this->callFactoryFunction( $functionName, $format, $options );

		$text = $formatter->format( $value );
		$this->assertRegExp( $expected, $text );
	}

	public function provideNewFormatter_noTitleLookup() {
		return [
			'plain item label' => [
				'newEntityIdFormatter',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions(),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'@^Label for Q5$@'
			],
			'item link' => [
				'newEntityIdFormatter',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'/^.*Label for Q5.*$/'
			]
		];
	}

	/**
	 * @dataProvider provideNewFormatter_LabelDescriptionLookupOption
	 */
	public function testNewFormatter_LabelDescriptionLookupOption(
		$functionName,
		FormatterOptions $options,
		DataValue $value,
		$expected
	) {
		$formatter = $this->callFactoryFunction( $functionName, SnakFormatter::FORMAT_HTML, $options );

		$text = $formatter->format( $value );
		$this->assertRegExp( $expected, $text );
	}

	public function provideNewFormatter_LabelDescriptionLookupOption() {
		$labelDescriptionLookup = $this->getMock( LabelDescriptionLookup::class );
		$labelDescriptionLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( new Term( 'xy', 'Custom LabelDescriptionLookup' ) ) );

		$fallbackFactory = new LanguageFallbackChainFactory();
		$fallbackChain = $fallbackFactory->newFromLanguage( Language::factory( 'de-ch' ) );

		return [
			'language option' => [
				'newEntityIdFormatter',
				new FormatterOptions( [
					ValueFormatter::OPT_LANG => 'de',
				] ),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'@>Name für Q5<@'
			],
			'fallback option' => [
				'newEntityIdFormatter',
				new FormatterOptions( [
					FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $fallbackChain,
				] ),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'@>Name für Q5<@'
			],
			'LabelDescriptionLookup option' => [
				'newEntityIdFormatter',
				new FormatterOptions( [
					FormatterLabelDescriptionLookupFactory::OPT_LABEL_DESCRIPTION_LOOKUP => $labelDescriptionLookup,
				] ),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'@>Custom LabelDescriptionLookup<@'
			],
		];
	}

	private function createCache() {
		return $this->prophesize( CacheInterface::class )->reveal();
	}

}
