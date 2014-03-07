<?php

namespace Wikibase\Lib\Test;

use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\WikibaseValueFormatterBuilders;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertyValueSnak;

/**
 * @covers Wikibase\Lib\WikibaseSnakFormatterBuilders
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikibaseSnakFormatterBuildersTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @param string $propertyType The property data type to use for all properties.
	 * @param EntityId $entityId   The Id of an entity to use for all entity lookups
	 *
	 * @return WikibaseSnakFormatterBuilders
	 */
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

		$entityTitleLookup = $this->getMock( 'Wikibase\EntityTitleLookup' );
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return \Title::newFromText( $id->getEntityType() . ':' . $id->getSerialization() );
			} ) );

		$lang = Language::factory( 'en' );

		$valueFormatterBuilders = new WikibaseValueFormatterBuilders( $entityLookup, $entityTitleLookup, $lang );
		return new WikibaseSnakFormatterBuilders( $valueFormatterBuilders, $typeLookup );
	}

	/**
	 * @covers WikibaseSnakFormatterBuilders::getSnakFormatterBuildersForFormats
	 */
	public function testGetSnakFormatterBuildersForFormats() {
		$builders = $this->newBuilders( 'string', new ItemId( 'Q5' ) );

		$buildersForFormats = $builders->getSnakFormatterBuildersForFormats();

		$requiredFormats = array(
			SnakFormatter::FORMAT_PLAIN,
			SnakFormatter::FORMAT_WIKI,
			SnakFormatter::FORMAT_HTML,
			SnakFormatter::FORMAT_HTML_WIDGET,
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
		$factory = new OutputFormatSnakFormatterFactory( $builders->getSnakFormatterBuildersForFormats() );

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
			ValueFormatter::OPT_LANG => 'en',
		) );

		$msg = wfMessage( 'wikibase-snakview-snaktypeselector-novalue' );
		$noValueMsg = $msg->inLanguage( 'en' )->text();

		$msg = wfMessage( 'wikibase-undeserializable-value' );
		$badValueMsg = $msg->inLanguage( 'en' )->text();

		return array(
			'plain url' => array(
				SnakFormatter::FORMAT_PLAIN,
				$options,
				'url',
				new PropertyValueSnak( 7, new StringValue( 'http://acme.com/' ) ),
				'http://acme.com/'
			),
			'wikitext no value' => array(
				SnakFormatter::FORMAT_WIKI,
				$options,
				'string',
				new PropertyNoValueSnak( 7 ),
				$noValueMsg
			),
			'html string' => array(
				SnakFormatter::FORMAT_HTML,
				$options,
				'string',
				new PropertyValueSnak( 7, new StringValue( 'I <3 Wikibase' ) ),
				'I &lt;3 Wikibase'
			),
			'plain item label (with entity lookup)' => array(
				SnakFormatter::FORMAT_PLAIN,
				$options,
				'wikibase-item',
				new PropertyValueSnak( 7, new EntityIdValue( new ItemId( 'Q5' ) ) ),
				'Label for Q5' // compare mock object created in newBuilders()
			),
			'diff url' => array(
				SnakFormatter::FORMAT_HTML_DIFF,
				$options,
				'url',
				new PropertyValueSnak( 7, new StringValue( 'http://acme.com/' ) ),
				'<a rel="nofollow" class="external free" href="http://acme.com/">http://acme.com/</a>'
			),
			'bad value' => array(
				SnakFormatter::FORMAT_PLAIN,
				$options,
				'globecoordinate',
				new PropertyValueSnak( 7,
					new UnDeserializableValue( 'cookie', 'globecoordinate', 'cannot understand!' )
				),
				$badValueMsg
			)
		);
	}

}
