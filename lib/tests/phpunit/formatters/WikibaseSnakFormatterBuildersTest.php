<?php
namespace Wikibase\Lib\Test;

use DataValues\StringValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\EntityFactory;
use Wikibase\Lib\SnakFormatterFactory;
use Wikibase\Lib\WikibaseSnakFormatterBuilders;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertyValueSnak;

/**
 * @covers Wikibase\Lib\WikibaseSnakFormatterBuilders
 *
 * @since 0.5
 *
 * @ingroup WikibaseLibTest
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikibaseSnakFormatterBuildersTest extends \PHPUnit_Framework_TestCase {

	public function newBuilders( $propertyType, EntityId $entityId ) {
		$typeLookup = $this->getMock( 'Wikibase\Lib\PropertyDataTypeLookup' );
		$typeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( $propertyType ) );

		$entity = EntityFactory::singleton()->newEmpty( $entityId->getEntityType() );
		$entity->setId( $entityId );
		$entity->setLabel( 'en', 'Label for ' . $entityId->getPrefixedId() );

		$entityLookup = $this->getMock( 'Wikibase\EntityLookup' );
		$entityLookup->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnValue( $entity ) );

		$lang = \Language::factory( 'en' );

		return new WikibaseSnakFormatterBuilders( $entityLookup, $typeLookup, $lang );
	}

	/**
	 * @covers WikibaseSnakFormatterBuilders::getSnakFormatterBuildersForFormats
	 */
	public function testGetSnakFormatterBuildersForFormats() {
		$builders = $this->newBuilders( 'string', new ItemId( 'Q5' ) );

		$buildersForFormats = $builders->getSnakFormatterBuildersForFormats();

		$requiredFormats = array(
			SnakFormatterFactory::FORMAT_PLAIN,
			SnakFormatterFactory::FORMAT_WIKI,
			SnakFormatterFactory::FORMAT_HTML,
			SnakFormatterFactory::FORMAT_HTML_WIDGET,
		);

		foreach ( $requiredFormats as $format ) {
			$this->assertArrayHasKey( $format, $buildersForFormats );
		}

		foreach ( $buildersForFormats as $builder ) {
			$this->assertTrue( is_callable( $builder ), 'callable' );
		}
	}

	/**
	 * @dataProvider buildDispatchingSnakFormatterProvider
	 * @covers WikibaseSnakFormatterBuilders::buildDispatchingSnakFormatter
	 */
	public function testBuildDispatchingSnakFormatter( $format, $options, $type, $snak, $expected ) {
		$builders = $this->newBuilders( $type, new ItemId( 'Q5' ) );
		$factory = new SnakFormatterFactory( $builders->getSnakFormatterBuildersForFormats() );

		$options = new FormatterOptions();

		$formatter = $builders->buildDispatchingSnakFormatter(
			$factory,
			$format,
			$options
		);

		$text = $formatter->formatSnak( $snak );
		$this->assertEquals( $expected, $text );
	}

	public function buildDispatchingSnakFormatterProvider() {
		$options = new FormatterOptions( array(
			'languages' => array( 'en' ),
		) );

		return array(
			'plain url' => array(
				SnakFormatterFactory::FORMAT_PLAIN,
				$options,
				'url',
				new PropertyValueSnak( 7, new StringValue( 'http://acme.com/' ) ),
				'http://acme.com/'
			),
			'wikitext no value' => array(
				SnakFormatterFactory::FORMAT_WIKI,
				$options,
				'string',
				new PropertyNoValueSnak( 7 ),
				wfMessage( 'wikibase-snakview-snaktypeselector-novalue' )->text()
			),
			'html string' => array(
				SnakFormatterFactory::FORMAT_HTML,
				$options,
				'string',
				new PropertyValueSnak( 7, new StringValue( 'I <3 Wikibase' ) ),
				'I &lt;3 Wikibase'
			),
			'widget item label (with entity lookup)' => array(
				SnakFormatterFactory::FORMAT_HTML_WIDGET,
				$options,
				'wikibase-item',
				new PropertyValueSnak( 7, new EntityIdValue( new ItemId( 'Q5' ) ) ),
				'Label for Q5' // compare mock object created in newBuilders()
			),
		);
	}

	/**
	 * @covers WikibaseSnakFormatterBuilders::getPlainTextFormatters
	 */
	public function testGetPlainTextFormatters() {
		$builders = $this->newBuilders( 'string', new ItemId( 'Q5' ) );
		$options = new FormatterOptions();

		// check for all the required types
		$requiredTypes = array(
			'VT:string',
			'VT:time',
			'VT:globecoordinate',
			'VT:wikibase-entityid',
		);

		$formatters = $builders->getPlainTextFormatters( $options );
		$formatterTypes = array_keys( $formatters );

		sort( $requiredTypes );
		sort( $formatterTypes );
		$this->assertEmpty( array_diff( $requiredTypes, $formatterTypes ), 'required' );

		// skip two of the required entries
		$skip = array_slice( $requiredTypes, 2 );
		$formatters = $builders->getPlainTextFormatters( $options, $skip );
		$this->assertEmpty( array_intersect( $skip, array_keys( $formatters ) ), 'skip' );
	}

	/**
	 * @covers WikibaseSnakFormatterBuilders::getWikiTextFormatters
	 */
	public function testGetWikiTextFormatters() {
		$builders = $this->newBuilders( 'string', new ItemId( 'Q5' ) );
		$options = new FormatterOptions();

		// check for all the required types, that is, the ones supported by the fallback format
		$requiredTypes = array_keys( $builders->getPlainTextFormatters( $options ) );
		$formatters = $builders->getWikiTextFormatters( $options );
		$formatterTypes = array_keys( $formatters );

		sort( $requiredTypes );
		sort( $formatterTypes );
		$this->assertEmpty( array_diff( $requiredTypes, $formatterTypes ), 'required' );

		// skip two of the required entries
		$skip = array_slice( $requiredTypes, 2 );
		$formatters = $builders->getWikiTextFormatters( $options, $skip );
		$this->assertEmpty( array_intersect( $skip, array_keys( $formatters ) ), 'skip' );
	}

	/**
	 * @covers WikibaseSnakFormatterBuilders::getHtmlFormatters
	 */
	public function testGetHtmlFormatters() {
		$builders = $this->newBuilders( 'string', new ItemId( 'Q5' ) );
		$options = new FormatterOptions();

		// check for all the required types, that is, the ones supported by the fallback format
		$requiredTypes = array_keys( $builders->getPlainTextFormatters( $options ) );
		$formatters = $builders->getHtmlFormatters( $options );
		$formatterTypes = array_keys( $formatters );

		sort( $requiredTypes );
		sort( $formatterTypes );
		$this->assertEmpty( array_diff( $requiredTypes, $formatterTypes ), 'required' );

		// skip two of the required entries
		$skip = array_slice( $requiredTypes, 2 );
		$formatters = $builders->getHtmlFormatters( $options, $skip );
		$this->assertEmpty( array_intersect( $skip, array_keys( $formatters ) ), 'skip' );
	}

	/**
	 * @covers WikibaseSnakFormatterBuilders::getWidgetFormatters
	 */
	public function testGetWidgetFormatters() {
		$builders = $this->newBuilders( 'string', new ItemId( 'Q5' ) );
		$options = new FormatterOptions();

		// check for all the required types, that is, the ones supported by the fallback format
		$requiredTypes = array_keys( $builders->getHtmlFormatters( $options ) );
		$formatters = $builders->getWidgetFormatters( $options );
		$formatterTypes = array_keys( $formatters );

		sort( $requiredTypes );
		sort( $formatterTypes );
		$this->assertEmpty( array_diff( $requiredTypes, $formatterTypes ), 'required' );

		// skip two of the required entries
		$skip = array_slice( $requiredTypes, 2 );
		$formatters = $builders->getWidgetFormatters( $options, $skip );
		$this->assertEmpty( array_intersect( $skip, array_keys( $formatters ) ), 'skip' );
	}

	/**
	 * @covers WikibaseSnakFormatterBuilders::makeEscapingFormatters
	 */
	public function testMakeEscapingFormatters() {
		$builders = $this->newBuilders( 'string', new ItemId( 'Q5' ) );

		$formatters = $builders->makeEscapingFormatters( array( new StringFormatter( new FormatterOptions() ) ), 'htmlspecialchars' );

		$text = $formatters[0]->format( new StringValue( 'I <3 Wikibase' ) );
		$this->assertEquals( 'I &lt;3 Wikibase', $text );
	}
}
