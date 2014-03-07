<?php
namespace Wikibase\Lib\Test;

use DataValues\StringValue;
use DataValues\QuantityValue;
use DataValues\TimeValue;
use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use ValueFormatters\ValueFormatter;
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

		$this->setMwGlobals( array(
			'wgArticlePath' => '/wiki/$1'
		) );
	}

	/**
	 * @param string $propertyType The property data type to use for all properties.
	 * @param EntityId $entityId   The Id of an entity to use for all entity lookups
	 *
	 * @return WikibaseValueFormatterBuilders
	 */
	public function newBuilders( $propertyType, EntityId $entityId ) {
		$entity = EntityFactory::singleton()->newEmpty( $entityId->getEntityType() );
		$entity->setId( $entityId );
		$entity->setLabel( 'en', 'Label for ' . $entityId->getPrefixedId() );

		$entityLookup = $this->getMock( 'Wikibase\EntityLookup' );
		$entityLookup->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnValue( $entity ) );

		$lang = Language::factory( 'en' );

		return new WikibaseValueFormatterBuilders( $entityLookup, $lang );
	}

	/**
	 * @dataProvider buildDispatchingValueFormatterProvider
	 * @covers WikibaseValueFormatterBuilders::buildDispatchingValueFormatter
	 */
	public function testBuildDispatchingValueFormatter( $format, $options, $snak, $expected, $dataType = null ) {
		$builders = $this->newBuilders( '-/-', new ItemId( 'Q5' ) );

		$factory = new OutputFormatValueFormatterFactory( $builders->getValueFormatterBuildersForFormats() );
		$formatter = $builders->buildDispatchingValueFormatter( $factory, $format, $options );

		$text = $formatter->formatValue( $snak, $dataType );
		$this->assertRegExp( $expected, $text );
	}

	public function buildDispatchingValueFormatterProvider() {
		$options = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => 'en',
		) );

		$optionsDe = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => 'de',
		) );

		return array(
			'plain url' => array(
				SnakFormatter::FORMAT_PLAIN,
				$options,
				new StringValue( 'http://acme.com/' ),
				'@^http://acme\\.com/$@'
			),
			'wikitext string' => array(
				SnakFormatter::FORMAT_WIKI,
				$options,
				new StringValue( '{Wikibase}' ),
				'@^&#123;Wikibase&#125;$@'
			),
			'html string' => array(
				SnakFormatter::FORMAT_HTML,
				$options,
				new StringValue( 'I <3 Wikibase & stuff' ),
				'@^I &lt;3 Wikibase &amp; stuff$@'
			),
			'plain item label (with entity lookup)' => array(
				SnakFormatter::FORMAT_PLAIN,
				$options,
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'@^Label for Q5$@' // compare mock object created in newBuilders()
			),
			'widget item link (with entity lookup)' => array(
				SnakFormatter::FORMAT_HTML_WIDGET,
				$options,
				new EntityIdValue( new ItemId( 'Q5' ) ),
				'@^<a href=".*/wiki/Q5">Label for Q5</a>$@', // compare mock object created in newBuilders()
				'wikibase-item'
			),
			'diff <url>' => array(
				SnakFormatter::FORMAT_HTML_DIFF,
				$options,
				new StringValue( '<http://acme.com/>' ),
				'@^&lt;http://acme\\.com/&gt;$@'
			),
			'localized quantity' => array(
				SnakFormatter::FORMAT_WIKI,
				$optionsDe,
				QuantityValue::newFromNumber( '+123456.789' ),
				'@^123\\.456,789$@'
			),
			'commons link' => array(
				SnakFormatter::FORMAT_HTML,
				$options,
				new StringValue( 'Example.jpg' ),
				'@^<a class="extiw" href="//commons\\.wikimedia\\.org/wiki/File:Example\\.jpg">Example\\.jpg</a>$@',
				'commonsMedia'
			),
		);
	}

	public function testSetValueFormatter() {
		$mockFormatter = $this->getMock( 'ValueFormatters\ValueFormatter' );
		$mockFormatter->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnValue( 'MOCK!' ) );

		$builders = $this->newBuilders( 'string', new ItemId( 'Q5' ) );
		$builders->setValueFormatter( SnakFormatter::FORMAT_PLAIN, 'VT:string', $mockFormatter );
		$builders->setValueFormatter( SnakFormatter::FORMAT_PLAIN, 'VT:time', null );

		$options = new FormatterOptions();
		$factory = new OutputFormatValueFormatterFactory( $builders->getValueFormatterBuildersForFormats() );
		$formatter = $builders->buildDispatchingValueFormatter( $factory, SnakFormatter::FORMAT_PLAIN, $options );

		$this->assertEquals( 'MOCK!', $formatter->format( new StringValue( 'o_O' ) ), 'Formatter override' );

		$this->setExpectedException( 'Wikibase\Lib\FormattingException' );

		$timeValue = new TimeValue( '+00000002013-01-01T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_SECOND, 'http://nyan.cat/original.php' );
		$formatter->format( $timeValue ); // expecting a FormattingException
	}

	public function testSetValueFormatterClass() {
		$builders = $this->newBuilders( 'string', new ItemId( 'Q5' ) );
		$builders->setValueFormatterClass( SnakFormatter::FORMAT_PLAIN, 'VT:wikibase-entityid', 'Wikibase\Lib\EntityIdFormatter' );
		$builders->setValueFormatterClass( SnakFormatter::FORMAT_PLAIN, 'VT:time', null );

		$options = new FormatterOptions();
		$factory = new OutputFormatValueFormatterFactory( $builders->getValueFormatterBuildersForFormats() );
		$formatter = $builders->buildDispatchingValueFormatter( $factory, SnakFormatter::FORMAT_PLAIN, $options );

		$this->assertEquals( 'Q5', $formatter->format( new EntityIdValue( new ItemId( "Q5" ) ) ), 'Extra formatter' );

		$this->setExpectedException( 'Wikibase\Lib\FormattingException' );

		$timeValue = new TimeValue( '+00000002013-01-01T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_SECOND, 'http://nyan.cat/original.php' );
		$formatter->format( $timeValue ); // expecting a FormattingException
	}

	public function testSetValueFormatterBuilder() {
		$builder = function () {
			$options = new FormatterOptions();
			return new EntityIdFormatter( $options );
		};

		$builders = $this->newBuilders( 'string', new ItemId( 'Q5' ) );
		$builders->setValueFormatterBuilder( SnakFormatter::FORMAT_PLAIN, 'VT:wikibase-entityid', $builder );
		$builders->setValueFormatterBuilder( SnakFormatter::FORMAT_PLAIN, 'VT:time', null );

		$options = new FormatterOptions();
		$factory = new OutputFormatValueFormatterFactory( $builders->getValueFormatterBuildersForFormats() );
		$formatter = $builders->buildDispatchingValueFormatter( $factory, SnakFormatter::FORMAT_PLAIN, $options );

		$this->assertEquals( 'Q5', $formatter->format( new EntityIdValue( new ItemId( "Q5" ) ) ), 'Extra formatter' );

		$this->setExpectedException( 'Wikibase\Lib\FormattingException' );

		$timeValue = new TimeValue( '+00000002013-01-01T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_SECOND, 'http://nyan.cat/original.php' );
		$formatter->format( $timeValue ); // expecting a FormattingException
	}

	/**
	 * @covers WikibaseValueFormatterBuilders::getPlainTextFormatters
	 */
	public function testGetPlainTextFormatters() {
		$builders = $this->newBuilders( 'string', new ItemId( 'Q5' ) );
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

	/**
	 * @covers WikibaseValueFormatterBuilders::getWikiTextFormatters
	 */
	public function testGetWikiTextFormatters() {
		$builders = $this->newBuilders( 'string', new ItemId( 'Q5' ) );
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

	/**
	 * @covers WikibaseValueFormatterBuilders::getHtmlFormatters
	 */
	public function testGetHtmlFormatters() {
		$builders = $this->newBuilders( 'string', new ItemId( 'Q5' ) );
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

	/**
	 * @covers WikibaseValueFormatterBuilders::getWidgetFormatters
	 */
	public function testGetWidgetFormatters() {
		$builders = $this->newBuilders( 'string', new ItemId( 'Q5' ) );
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

	/**
	 * @covers WikibaseValueFormatterBuilders::getDiffFormatters
	 */
	public function testGetDiffFormatters() {
		$builders = $this->newBuilders( 'string', new ItemId( 'Q5' ) );
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

	/**
	 * @covers WikibaseValueFormatterBuilders::makeEscapingFormatters
	 */
	public function testMakeEscapingFormatters() {
		$builders = $this->newBuilders( 'string', new ItemId( 'Q5' ) );

		$formatters = $builders->makeEscapingFormatters( array( new StringFormatter( new FormatterOptions() ) ), 'htmlspecialchars' );

		$text = $formatters[0]->format( new StringValue( 'I <3 Wikibase' ) );
		$this->assertEquals( 'I &lt;3 Wikibase', $text );
	}

	/**
	 * @dataProvider applyLanguageDefaultsProvider
	 */
	public function testApplyLanguageDefaults( FormatterOptions $options, $expectedLanguage, $expectedFallback ) {
		$builders = $this->newBuilders( 'string', new ItemId( 'Q5' ) );

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
