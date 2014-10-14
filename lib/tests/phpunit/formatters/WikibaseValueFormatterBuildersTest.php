<?php

namespace Wikibase\Lib\Test;

use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use ValueFormatters\TimeFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityFactory;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\OutputFormatValueFormatterFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\WikibaseValueFormatterBuilders;

/**
 * @covers Wikibase\Lib\WikibaseValueFormatterBuilders
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikibaseValueFormatterBuildersTest extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
		$this->setMwGlobals( 'wgArticlePath', '/wiki/$1' );
	}

	/**
	 * @param EntityId $entityId The Id of an entity to use for all entity lookups
	 * @return WikibaseValueFormatterBuilders
	 */
	private function newWikibaseValueFormatterBuilders( EntityId $entityId ) {
		$entity = EntityFactory::singleton()->newEmpty( $entityId->getEntityType() );
		$entity->setId( $entityId );
		$entity->setLabel( 'en', 'Label for ' . $entityId->getSerialization() );

		return new WikibaseValueFormatterBuilders(
			$this->getEntityLookup( $entity ),
			$this->getTermLookup(),
			Language::factory( 'en' )
		);
	}

	private function getEntityLookup( Entity $entity ) {
		$entityLookup = $this->getMock( 'Wikibase\Lib\Store\EntityLookup' );
		$entityLookup->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnValue( $entity ) );

		return $entityLookup;
	}

	private function getTermLookup() {
		$termLookup = $this->getMockBuilder( 'Wikibase\Lib\Store\TermLookup' )
			->disableOriginalConstructor()
			->getMock();

		$termLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnCallback( function( EntityId $entityId ) {
				return $entityId->getSerialization() === 'Q5' ? 'Label for Q5' : null;
			} ) );

		return $termLookup;
	}

	private function newFormatterOptions( $lang = 'en' ) {
		return new FormatterOptions( array(
			ValueFormatter::OPT_LANG => $lang,
		) );
	}

	/**
	 * @dataProvider buildDispatchingValueFormatterProvider
	 */
	public function testBuildDispatchingValueFormatter( $format, $options, $snak, $expected, $dataTypeId = null ) {
		$builders = $this->newWikibaseValueFormatterBuilders( new ItemId( 'Q5' ) );

		$factory = new OutputFormatValueFormatterFactory( $builders->getValueFormatterBuildersForFormats() );
		$formatter = $builders->buildDispatchingValueFormatter( $factory, $format, $options );

		$text = $formatter->formatValue( $snak, $dataTypeId );
		$this->assertRegExp( $expected, $text );
	}

	public function buildDispatchingValueFormatterProvider() {
		return array(
			'plain url' => array(
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions(),
				new StringValue( 'http://acme.com/' ),
				'@^http://acme\\.com/$@'
			),
			'wikitext string' => array(
				SnakFormatter::FORMAT_WIKI,
				$this->newFormatterOptions(),
				new StringValue( '{Wikibase}' ),
				'@^&#123;Wikibase&#125;$@'
			),
			'html string' => array(
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new StringValue( 'I <3 Wikibase & stuff' ),
				'@^I &lt;3 Wikibase &amp; stuff$@'
			),
			'plain item label (with entity lookup)' => array(
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions(),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'@^Label for Q5$@' // compare mock object created in newBuilders()
			),
			'widget item link (with entity lookup)' => array(
				SnakFormatter::FORMAT_HTML_WIDGET,
				$this->newFormatterOptions(),
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'/^<a\b[^>]* href="[^"]*\bQ5">Label for Q5<\/a>.*$/', // compare mock object created in newBuilders()
				'wikibase-item'
			),
			'diff <url>' => array(
				SnakFormatter::FORMAT_HTML_DIFF,
				$this->newFormatterOptions(),
				new StringValue( '<http://acme.com/>' ),
				'@^&lt;http://acme\\.com/&gt;$@'
			),
			'localized quantity' => array(
				SnakFormatter::FORMAT_WIKI,
				$this->newFormatterOptions( 'de' ),
				QuantityValue::newFromNumber( '+123456.789' ),
				'@^123\\.456,789$@'
			),
			'commons link' => array(
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new StringValue( 'Example.jpg' ),
				'@^<a class="extiw" href="//commons\\.wikimedia\\.org/wiki/File:Example\\.jpg">Example\\.jpg</a>$@',
				'commonsMedia'
			),
			'a month in 1920' => array(
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new TimeValue( '+1920-05-01T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_MONTH,
					TimeFormatter::CALENDAR_GREGORIAN ),
				'/^May 1920$/'
			),
			'a gregorian day in 1520' => array(
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new TimeValue( '+1520-05-01T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_DAY,
					TimeFormatter::CALENDAR_GREGORIAN ),
				'/^1 May 1520 <sup class="wb-calendar-name">Gregorian<\/sup>$/'
			),
			'a julian day in 1980' => array(
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions(),
				new TimeValue( '+1980-05-01T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_DAY,
					TimeFormatter::CALENDAR_JULIAN ),
				'/^1 May 1980 <sup class="wb-calendar-name">Julian<\/sup>$/'
			),
			'text in english' => array(
				SnakFormatter::FORMAT_PLAIN,
				$this->newFormatterOptions( 'en' ),
				new MonolingualTextValue( 'en', 'Hello World' ),
				'/^Hello World$/'
			),
			'text in german' => array(
				SnakFormatter::FORMAT_HTML,
				$this->newFormatterOptions( 'en' ),
				new MonolingualTextValue( 'de', 'Hallo Welt' ),
				'/^<span lang="de".*?>Hallo Welt<\/span>.*\((German|Deutsch)\).*$/'
			),
			'text in spanish' => array(
				SnakFormatter::FORMAT_WIKI,
				$this->newFormatterOptions( 'de' ),
				new MonolingualTextValue( 'es', 'Ola' ),
				'/^Ola$/u'
			),
		);
	}

	public function testSetValueFormatter() {
		$mockFormatter = $this->getMock( 'ValueFormatters\ValueFormatter' );
		$mockFormatter->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnValue( 'MOCK!' ) );

		$builders = $this->newWikibaseValueFormatterBuilders( new ItemId( 'Q5' ) );
		$builders->setValueFormatter( SnakFormatter::FORMAT_PLAIN, 'VT:string', $mockFormatter );
		$builders->setValueFormatter( SnakFormatter::FORMAT_PLAIN, 'VT:time', null );

		$formatter = $builders->buildDispatchingValueFormatter(
			new OutputFormatValueFormatterFactory(
				$builders->getValueFormatterBuildersForFormats()
			),
			SnakFormatter::FORMAT_PLAIN,
			new FormatterOptions()
		);

		$this->assertEquals(
			'MOCK!',
			$formatter->format( new StringValue( 'o_O' ) ),
			'Formatter override'
		);

		$this->setExpectedException( 'ValueFormatters\FormattingException' );

		$timeValue = new TimeValue(
			'+00000002013-01-01T00:00:00Z',
			0,
			0,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php'
		);
		$formatter->format( $timeValue ); // expecting a FormattingException
	}

	public function testSetValueFormatterClass() {
		$builders = $this->newWikibaseValueFormatterBuilders( new ItemId( 'Q5' ) );
		$builders->setValueFormatterClass(
			SnakFormatter::FORMAT_PLAIN,
			'VT:wikibase-entityid',
			'Wikibase\Lib\EntityIdFormatter'
		);
		$builders->setValueFormatterClass(
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
			'+00000002013-01-01T00:00:00Z',
			0,
			0,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php'
		);

		$formatter->format( $timeValue ); // expecting a FormattingException
	}

	public function testSetValueFormatterBuilder() {
		$builder = function () {
			$options = new FormatterOptions();
			return new EntityIdFormatter( $options );
		};

		$builders = $this->newWikibaseValueFormatterBuilders( new ItemId( 'Q5' ) );
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
			'+00000002013-01-01T00:00:00Z',
			0,
			0,
			0,
			TimeValue::PRECISION_SECOND,
			'http://nyan.cat/original.php'
		);
		$formatter->format( $timeValue ); // expecting a FormattingException
	}

	public function testGetPlainTextFormatters() {
		$builders = $this->newWikibaseValueFormatterBuilders( new ItemId( 'Q5' ) );
		$options = new FormatterOptions();

		// check for all the required types
		$required = array(
			'VT:string',
			'VT:time',
			'VT:globecoordinate',
			'VT:wikibase-entityid',
			'VT:quantity',
		);

		// check for all the required types, that is, the ones supported by the fallback format
		$this->assertIncluded(
			$required,
			array_keys( $builders->getPlainTextFormatters( $options ) )
		);

		// skip two of the required entries
		$skip = array_slice( $required, 2 );
		$this->assertExcluded(
			$skip,
			array_keys( $builders->getPlainTextFormatters( $options, $skip ) )
		);
	}

	public function testGetWikiTextFormatters() {
		$builders = $this->newWikibaseValueFormatterBuilders( new ItemId( 'Q5' ) );
		$options = new FormatterOptions();

		// check for all the required types, that is, the ones supported by the fallback format
		$required = array_keys( $builders->getPlainTextFormatters( $options ) );
		$this->assertIncluded(
			$required,
			array_keys( $builders->getWikiTextFormatters( $options ) )
		);

		// skip two of the required entries
		$skip = array_slice( $required, 2 );
		$this->assertExcluded(
			$skip,
			array_keys( $builders->getWikiTextFormatters( $options, $skip ) )
		);
	}

	public function testGetHtmlFormatters() {
		$builders = $this->newWikibaseValueFormatterBuilders( new ItemId( 'Q5' ) );
		$options = new FormatterOptions();

		// check for all the required types, that is, the ones supported by the fallback format
		$required = array_keys( $builders->getPlainTextFormatters( $options ) );
		$this->assertIncluded(
			$required,
			array_keys( $builders->getHtmlFormatters( $options ) )
		);

		// skip two of the required entries
		$skip = array_slice( $required, 2 );
		$this->assertExcluded(
			$skip,
			array_keys( $builders->getHtmlFormatters( $options, $skip ) )
		);
	}

	public function testGetWidgetFormatters() {
		$builders = $this->newWikibaseValueFormatterBuilders( new ItemId( 'Q5' ) );
		$options = new FormatterOptions();

		// check for all the required types, that is, the ones supported by the fallback format
		$required = array_keys( $builders->getHtmlFormatters( $options ) );
		$this->assertIncluded(
			$required,
			array_keys( $builders->getWidgetFormatters( $options ) )
		);

		// skip two of the required entries
		$skip = array_slice( $required, 2 );
		$this->assertExcluded(
			$skip,
			array_keys( $builders->getWidgetFormatters( $options, $skip ) )
		);
	}

	public function testGetDiffFormatters() {
		$builders = $this->newWikibaseValueFormatterBuilders( new ItemId( 'Q5' ) );
		$options = new FormatterOptions();

		// check for all the required types, that is, the ones supported by the fallback format
		$required = array_keys( $builders->getHtmlFormatters( $options ) );
		$this->assertIncluded(
			$required,
			array_keys( $builders->getDiffFormatters( $options ) )
		);

		// skip two of the required entries
		$skip = array_slice( $required, 2 );
		$this->assertExcluded(
			$skip,
			array_keys( $builders->getDiffFormatters( $options, $skip ) )
		);
	}

	/**
	 * Asserts that $actualTypes contains all types listed in $requiredTypes.
	 *
	 * @param string[] $requiredTypes
	 * @param string[] $actualTypes
	 */
	protected function assertIncluded( $requiredTypes, $actualTypes ) {
		sort( $requiredTypes );
		sort( $actualTypes );
		$this->assertEmpty( array_diff( $requiredTypes, $actualTypes ), 'required' );
	}

	/**
	 * Asserts that $actualTypes does not contain types listed in $skippedTypes.
	 *
	 * @param string[] $skippedTypes
	 * @param string[] $actualTypes
	 */
	protected function assertExcluded( $skippedTypes, $actualTypes ) {
		sort( $skippedTypes );
		sort( $actualTypes );
		$this->assertEmpty( array_intersect( $skippedTypes, $actualTypes ), 'skipped' );
	}

	public function testMakeEscapingFormatters() {
		$builders = $this->newWikibaseValueFormatterBuilders( new ItemId( 'Q5' ) );

		$formatters = $builders->makeEscapingFormatters(
			array( new StringFormatter( new FormatterOptions() ) ),
			'htmlspecialchars'
		);

		$text = $formatters[0]->format( new StringValue( 'I <3 Wikibase' ) );
		$this->assertEquals( 'I &lt;3 Wikibase', $text );
	}

	/**
	 * @dataProvider applyLanguageDefaultsProvider
	 */
	public function testApplyLanguageDefaults( FormatterOptions $options, $expectedLanguage, $expectedFallback ) {
		$builders = $this->newWikibaseValueFormatterBuilders( new ItemId( 'Q5' ) );

		$builders->applyLanguageDefaults( $options );

		if ( $expectedLanguage !== null ) {
			$lang = $options->getOption( ValueFormatter::OPT_LANG );
			$this->assertEquals( $expectedLanguage, $lang, 'option: ' . ValueFormatter::OPT_LANG );
		}

		if ( $expectedFallback !== null ) {
			/* @var LanguageFallbackChain $languageFallback */
			$languageFallback = $options->getOption( 'languages' );
			$languages = $languageFallback->getFallbackChain();
			$lang = $languages[0]->getLanguage()->getCode();

			$this->assertEquals( $expectedFallback, $lang, 'option: languages' );
		}
	}

	public function applyLanguageDefaultsProvider() {
		$languageFallbackFactory = new LanguageFallbackChainFactory();
		$languageFallback = $languageFallbackFactory->newFromLanguage( Language::factory( 'fr' ) );

		return array(
			'empty' => array(
				new FormatterOptions( array( ) ),
				'en', // determined in WikibaseValueFormatterBuildersTest::newBuilder()
				'en'  // derived from language code
			),
			'language code set' => array(
				new FormatterOptions( array( ValueFormatter::OPT_LANG => 'de' ) ),
				'de', // as given
				'de'  // derived from language code
			),
			'language fallback set' => array(
				new FormatterOptions( array( 'languages' => $languageFallback ) ),
				'en', // default code is taken from the constructor, not the fallback chain
				'fr'  // as given
			),
			'language code and fallback set' => array(
				new FormatterOptions( array( ValueFormatter::OPT_LANG => 'de', 'languages' => $languageFallback ) ),
				'de', // as given
				'fr'  // as given
			),
		);
	}

}
