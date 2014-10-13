<?php

namespace Wikibase\Lib\Test;

use DataTypes\DataType;
use DataValues\StringValue;
use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\EntityFactory;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\WikibaseValueFormatterBuilders;

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
		$typeLookup = $this->getMock( 'Wikibase\DataModel\Entity\PropertyDataTypeLookup' );
		$typeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( $propertyType ) );

		$typeMap = array(
			'url' => 'string',
			'string' => 'string',
			'wikibase-item' => 'wikibase-entityid',
			'globecoordinate' => 'globecoordinate',
		);

		$typeFactory = $this->getMock( 'DataTypes\DataTypeFactory' );
		$typeFactory->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnCallback( function ( $id ) use ( $typeMap ) {
				return new DataType( $id, $typeMap[$id], array() );
			} ) );

		$entity = EntityFactory::singleton()->newEmpty( $entityId->getEntityType() );
		$entity->setId( $entityId );
		$entity->setLabel( 'en', 'Label for ' . $entityId->getSerialization() );

		$entityLookup = $this->getMock( 'Wikibase\Lib\Store\EntityLookup' );
		$entityLookup->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnValue( $entity ) );

		$lang = Language::factory( 'en' );

		$valueFormatterBuilders = new WikibaseValueFormatterBuilders( $entityLookup, $lang );
		return new WikibaseSnakFormatterBuilders( $valueFormatterBuilders, $typeLookup, $typeFactory );
	}

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
			)
		);
	}

}
