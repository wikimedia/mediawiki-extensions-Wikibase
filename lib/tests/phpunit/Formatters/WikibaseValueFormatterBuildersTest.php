<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\DataValue;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use DataValues\UnboundedQuantityValue;
use DataValues\UnDeserializableValue;
use Language;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Psr\SimpleCache\CacheInterface;
use Title;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\Formatters\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\Formatters\ShowCalendarModelDecider;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Formatters\WikibaseValueFormatterBuilders;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityExistenceChecker;
use Wikibase\Lib\Store\EntityRedirectChecker;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\Tests\FakeCache;

/**
 * @covers \Wikibase\Lib\Formatters\WikibaseValueFormatterBuilders
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikibaseValueFormatterBuildersTest extends MediaWikiIntegrationTestCase {

	private const GEO_SHAPE_STORAGE_FRONTEND_URL = '//commons.wikimedia.org/wiki/';

	private const TABULAR_DATA_STORAGE_FRONTEND_URL = '//commons2.wikimedia.org/wiki/';

	protected function setUp(): void {
		parent::setUp();

		$this->setMwGlobals( 'wgArticlePath', '/wiki/$1' );
	}

	/**
	 * @param EntityTitleLookup $entityTitleLookup
	 *
	 * @return WikibaseValueFormatterBuilders
	 */
	private function newWikibaseValueFormatterBuilders( EntityTitleLookup $entityTitleLookup ) {
		$termLookup = $this->createMock( TermLookup::class );

		$termLookup->method( 'getLabel' )
			->willReturnCallback( function ( EntityId $id, $language ) {
				switch ( $language ) {
					case 'de':
						return 'Name für ' . $id->getSerialization();
					default:
						return 'Label for ' . $id->getSerialization();
				}
			} );

		$termLookup->method( 'getLabels' )
			->willReturnCallback( function( EntityId $id ) {
				return [
					'de' => 'Name für ' . $id->getSerialization(),
					'en' => 'Label for ' . $id->getSerialization(),
				];
			} );

		$languageNameLookup = $this->createMock( LanguageNameLookup::class );
		$languageNameLookup->method( 'getName' )
			->willReturn( 'Deutsch' );

		$urlLookup = $this->createMock( EntityUrlLookup::class );
		$urlLookup->method( 'getLinkUrl' )
			->willReturnCallback( function ( EntityId $id ) {
				return '/wiki/' . $id->getSerialization();
			} );

		$redirectResolvingLatestRevisionLookup = $this->createStub( RedirectResolvingLatestRevisionLookup::class );
		$redirectResolvingLatestRevisionLookup->method( 'lookupLatestRevisionResolvingRedirect' )
			->willReturnCallback( function ( EntityId $id ) {
				return [
					123, // some non-null revision id
					$id,
				];
			} );

		return new WikibaseValueFormatterBuilders(
			new FormatterLabelDescriptionLookupFactory(
				$termLookup,
				new TermFallbackCacheFacade( new FakeCache(), 999 ),
				$redirectResolvingLatestRevisionLookup
			),
			$languageNameLookup,
			new ItemIdParser(),
			self::GEO_SHAPE_STORAGE_FRONTEND_URL,
			self::TABULAR_DATA_STORAGE_FRONTEND_URL,
			$this->createCache(),
			$this->createMock( EntityLookup::class ),
			$redirectResolvingLatestRevisionLookup,
			1,
			$this->createMock( EntityExistenceChecker::class ),
			$this->createMock( EntityTitleTextLookup::class ),
			$urlLookup,
			$this->createMock( EntityRedirectChecker::class ),
			$this->getServiceContainer()->getLanguageFactory(),
			$entityTitleLookup,
			$this->newKartographerEmbeddingHandler(),
			true,
			[ 120 ]
		);
	}

	private function newKartographerEmbeddingHandler() {
		$handler = $this->createMock( CachingKartographerEmbeddingHandler::class );

		$handler->method( 'getHtml' )
			->with(
				$this->isInstanceOf( GlobeCoordinateValue::class ),
				$this->isInstanceOf( Language::class )
			)
			->willReturn( '<kartographer-html/>' );

		$handler->method( 'getPreviewHtml' )
			->with(
				$this->isInstanceOf( GlobeCoordinateValue::class ),
				$this->isInstanceOf( Language::class )
			)
			->willReturn( '<kartographer-preview-html/>' );

		return $handler;
	}

	private function newFormatterOptions( $lang = 'en', $otherOptions = [] ) {
		$fallbackChainFactory = new LanguageFallbackChainFactory();
		$fallbackChain = $fallbackChainFactory->newFromLanguageCode( $lang );

		return new FormatterOptions( array_merge( $otherOptions, [
			ValueFormatter::OPT_LANG => $lang,
			FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $fallbackChain,
		] ) );
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getTitleLookup() {
		$titleLookup = $this->createMock( EntityTitleLookup::class );

		$titleLookup->method( 'getTitleForId' )
			->willReturnCallback(
				function ( EntityId $id ) {
					return Title::makeTitle( NS_MAIN, $id->getSerialization() );
				}
			);

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
			'newEntitySchemaFormatter',
			'newEntityIdFormatter',
			'newMonolingualFormatter',
			'newTimeFormatter',
			'newGlobeCoordinateFormatter',
			'newQuantityFormatter',
		];

		$options = new FormatterOptions();
		$options->defaultOption( ValueFormatter::OPT_LANG, 'en' );

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
		$this->assertMatchesRegularExpression( $expected, $text );
	}

	public function provideNewFormatter() {
		return [
			// String
			'plain string' => [
				'String',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'en' ),
				new StringValue( 'foo bar' ),
				'@^foo bar$@',
			],
			'wikitext string' => [
				'String',
				SnakFormatter::FORMAT_WIKI,
				$this->newFormatterOptions( 'en' ),
				new StringValue( 'foo[bar]' ),
				'@^foo&#91;bar&#93;$@',
			],
			'html string' => [
				'String',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions( 'en' ),
				new StringValue( 'foo<bar>' ),
				'@^foo&lt;bar&gt;$@',
			],

			// UnDeserializableValue
			'plain bad value' => [
				'UnDeserializableValue',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'en' ),
				new UnDeserializableValue( 'foo bar', 'xyzzy', 'broken' ),
				'@invalid@',
			],

			// Url
			'plain url' => [
				'Url',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'en' ),
				new StringValue( 'http://acme.com/' ),
				'@^http://acme\.com/$@',
			],
			'wikitext url' => [
				'Url',
				SnakFormatter::FORMAT_WIKI,
				$this->newFormatterOptions( 'en' ),
				new StringValue( 'http://acme.com/' ),
				'@^http://acme\.com/$@',
			],
			'html url' => [
				'Url',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions( 'en' ),
				new StringValue( 'http://acme.com/' ),
				'@^.*href="http://acme.com/".*$@',
			],

			// EntityId
			'plain item label (with language fallback)' => [
				'EntityId',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'de-ch' ), // should fall back to 'de'
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'@^Name für Q5$@', // compare mock object created in newBuilders()
			],
			'item link (with entity lookup)' => [
				'EntityId',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions( 'en' ),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'/^<a\b[^>]* href="[^"]*\bQ5">Label for Q5<\/a>.*$/', // compare mock object created in newBuilders()
			],
			'property link (with entity lookup)' => [
				'EntityId',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions( 'en' ),
				new EntityIdValue( new NumericPropertyId( 'P5' ) ),
				'/^<a\b[^>]* href="[^"]*\bP5">Label for P5<\/a>.*$/',
			],

			// CommonsMedia
			'plain commons media' => [
				'CommonsMedia',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'en' ),
				new StringValue( 'Example.jpg' ),
				'@^Example.jpg$@',
			],
			'html commons link' => [
				'CommonsMedia',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions( 'en' ),
				new StringValue( 'Example.jpg' ),
				'@^<a class="extiw" href="//commons\.wikimedia\.org/wiki/File:Example\.jpg">Example\.jpg</a>$@',
			],
			'html commons inline image' => [
				'CommonsMedia',
				SnakFormatter::FORMAT_HTML_VERBOSE,
				$this->newFormatterOptions( 'en' ),
				new StringValue( 'DOES-NOT-EXIST-dfsdf.jpg' ),
				'@^<div.*>.*<a.*href="https://commons\.wikimedia\.org/.*DOES-NOT-EXIST.*>.*</div>$@s',
			],
			// geo-shape
			'plain geo-shape' => [
				'GeoShape',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'en' ),
				new StringValue( 'Data:GeoShape.map' ),
				'@^Data:GeoShape.map$@',
			],
			'html geo-shape' => [
				'GeoShape',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions( 'en' ),
				new StringValue( 'Data:GeoShape.map' ),
				'@^<a class="extiw" href="//commons\.wikimedia\.org/wiki/Data:GeoShape\.map">Data:GeoShape\.map</a>$@',
			],
			'wikitext geo-shape' => [
				'GeoShape',
				SnakFormatter::FORMAT_WIKI,
				$this->newFormatterOptions( 'en' ),
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
				$this->newFormatterOptions( 'en' ),
				new StringValue( 'Data:TabularData.tab' ),
				'@^Data:TabularData.tab$@',
			],
			'html tabular-data' => [
				'TabularData',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions( 'en' ),
				new StringValue( 'Data:TabularData.tab' ),
				'@^<a class="extiw" href="//commons2\.wikimedia\.org/wiki/Data:TabularData\.tab">Data:TabularData\.tab</a>$@',
			],
			'wikitext tabular-data' => [
				'TabularData',
				SnakFormatter::FORMAT_WIKI,
				$this->newFormatterOptions( 'en' ),
				new StringValue( 'Data:TabularData.tab' ),
				'@' . preg_quote(
					'[//commons2.wikimedia.org/wiki/Data:TabularData.tab Data:TabularData.tab]',
					'@'
				) . '@',
			],
			// entity-schema
			'plain entity-schema' => [
				'EntitySchema',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'en' ),
				new StringValue( 'E45' ),
				'@^E45$@',
			],
			'html entity-schema' => [
				'EntitySchema',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions( 'en' ),
				new StringValue( 'E45' ),
				'@^<a href=".+?:E45">E45</a>$@',
			],
			'wikitext entity-schema' => [
				'EntitySchema',
				SnakFormatter::FORMAT_WIKI,
				$this->newFormatterOptions( 'en' ),
				new StringValue( 'E45' ),
				'@^\[\[.+?:E45\|E45]]$@',
			],
			// GlobeCoordinate
			'plain coordinate' => [
				'GlobeCoordinate',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'en' ),
				new GlobeCoordinateValue( new LatLongValue( -55.755786, 37.25633 ), 0.25 ),
				'@^55°45\'S, 37°15\'E$@',
			],
			'wikitext coordinate' => [
				'GlobeCoordinate',
				SnakFormatter::FORMAT_WIKI,
				$this->newFormatterOptions( 'en' ),
				new GlobeCoordinateValue( new LatLongValue( 1, 2 ), 1 ),
				'@^<maplink latitude="1" longitude="2">{"type":"Feature","geometry":{"type":"Point","coordinates":\[2,1\]}}</maplink>$@',
			],
			'coordinate details' => [
				'GlobeCoordinate',
				SnakFormatter::FORMAT_HTML_DIFF,
				$this->newFormatterOptions( 'de' ),
				new GlobeCoordinateValue( new LatLongValue( -55.755786, 37.25633 ), 0.25 ),
				'@^.*55° 45\', 37° 15\'.*$@',
			],
			'coordinate kartographer html' => [
				'GlobeCoordinate',
				SnakFormatter::FORMAT_HTML_VERBOSE,
				$this->newFormatterOptions( 'de' ),
				new GlobeCoordinateValue( new LatLongValue( -55.755786, 37.25633 ), 0.25 ),
				'@^<div><kartographer-html/><div class="wikibase-kartographer-caption">55°45&apos;S, 37°15&apos;E</div></div>$@',
			],
			'coordinate kartographer preview html' => [
				'GlobeCoordinate',
				SnakFormatter::FORMAT_HTML_VERBOSE_PREVIEW,
				$this->newFormatterOptions( 'de' ),
				new GlobeCoordinateValue( new LatLongValue( -55.755786, 37.25633 ), 0.25 ),
				'@^<div><kartographer-preview-html/><div class="wikibase-kartographer-caption">55°45&apos;S, 37°15&apos;E</div></div>$@',
			],

			// Quantity
			'localized quantity' => [
				'Quantity',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'de' ),
				UnboundedQuantityValue::newFromNumber( '+123456.789' ),
				'@^123\.456,789$@',
			],
			'quantity details' => [
				'Quantity',
				SnakFormatter::FORMAT_HTML_DIFF,
				$this->newFormatterOptions( 'de' ),
				QuantityValue::newFromNumber( '+123456.789' ),
				'@^.*123\.456,789.*$@',
			],

			// Time
			'a month in 1980' => [
				'Time',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'en' ),
				new TimeValue(
					'+1980-05-01T00:00:00Z',
					0, 0, 0,
					TimeValue::PRECISION_MONTH,
					'http://www.wikidata.org/entity/Q1985727'
				),
				'/^May 1980$/',
			],
			'a gregorian day in 1520' => [
				'Time',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions( 'en' ),
				new TimeValue(
					'+1520-05-01T00:00:00Z',
					0, 0, 0,
					TimeValue::PRECISION_DAY,
					'http://www.wikidata.org/entity/Q1985727'
				),
				'/^1 May 1520<sup class="wb-calendar-name">Gregorian<\/sup>$/',
			],
			'a gregorian day in 1520 (plain, showcalendar=auto)' => [
				'Time',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'en',
					[ ShowCalendarModelDecider::OPT_SHOW_CALENDAR => 'auto' ] ),
				new TimeValue(
					'+1520-05-01T00:00:00Z',
					0, 0, 0,
					TimeValue::PRECISION_DAY,
					'http://www.wikidata.org/entity/Q1985727'
				),
				'/^1 May 1520 \(Gregorian\)$/',
			],
			'a julian day in 1980' => [
				'Time',
				SnakFormatter::FORMAT_HTML_DIFF,
				$this->newFormatterOptions( 'en' ),
				new TimeValue(
					'+1980-05-01T00:00:00Z',
					0, 0, 0,
					TimeValue::PRECISION_DAY,
					'http://www.wikidata.org/entity/Q1985786'
				),
				'/^.*>1 May 1980<sup class="wb-calendar-name">Julian<\/sup>.*$/',
			],
			'a julian day in 1980 (showcalendar=false)' => [
				'Time',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions( 'en',
					[ ShowCalendarModelDecider::OPT_SHOW_CALENDAR => false ] ),
				new TimeValue(
					'+1980-05-01T00:00:00Z',
					0, 0, 0,
					TimeValue::PRECISION_DAY,
					'http://www.wikidata.org/entity/Q1985786'
				),
				'/^1 May 1980$/',
			],

			// Monolingual
			'text in english' => [
				'Monolingual',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'en' ),
				new MonolingualTextValue( 'en', 'Hello World' ),
				'/^Hello World$/',
			],
			'text in german' => [
				'Monolingual',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions( 'en' ),
				new MonolingualTextValue( 'de', 'Hallo Welt' ),
				'/ lang="de".*>Hallo Welt<.*Deutsch/',
			],
			'wikitext monolingual text' => [
				'Monolingual',
				SnakFormatter::FORMAT_WIKI,
				$this->newFormatterOptions( 'en' ),
				new MonolingualTextValue( 'de', 'Hallo Welt' ),
				'/ lang="de".*>Hallo Welt</',
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
		$this->assertMatchesRegularExpression( $expected, $text );
	}

	public function provideNewFormatter_noTitleLookup() {
		return [
			'plain item label' => [
				'newEntityIdFormatter',
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'en' ),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'@^Label for Q5$@',
			],
			'item link' => [
				'newEntityIdFormatter',
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions( 'en' ),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'/^.*Label for Q5.*$/',
			],
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
		$this->assertMatchesRegularExpression( $expected, $text );
	}

	public function provideNewFormatter_LabelDescriptionLookupOption() {
		$labelDescriptionLookup = $this->createMock( LabelDescriptionLookup::class );
		$labelDescriptionLookup->method( 'getLabel' )
			->willReturn( new Term( 'xy', 'Custom LabelDescriptionLookup' ) );

		$fallbackFactory = new LanguageFallbackChainFactory();
		$lang = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'de-ch' );
		$fallbackChain = $fallbackFactory->newFromLanguage( $lang );

		return [
			'language option' => [
				'newEntityIdFormatter',
				new FormatterOptions( [
					ValueFormatter::OPT_LANG => 'de',
				] ),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'@>Name für Q5<@',
			],
			'fallback option' => [
				'newEntityIdFormatter',
				new FormatterOptions( [
					FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $fallbackChain,
				] ),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'@>Name für Q5<@',
			],
		];
	}

	private function createCache() {
		return new TermFallbackCacheFacade(
			$this->createMock( CacheInterface::class ),
			10
		);
	}

}
