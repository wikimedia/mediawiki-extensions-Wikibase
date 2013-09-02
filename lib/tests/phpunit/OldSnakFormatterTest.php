<?php

namespace Wikibase\Lib\Test;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use DataValues\StringValue;
use ValueFormatters\FormatterOptions;
use ValueParsers\ParserOptions;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Item;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\EntityIdParser;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\Lib\OldSnakFormatter;
use Wikibase\Lib\TypedValueFormatter;
use Wikibase\Property;
use Wikibase\PropertyValueSnak;

/**
 * @covers Wikibase\Lib\SnakFormatter
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group WikibaseLib
 * @group OldSnakFormatterTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class OldSnakFormatterTest extends \PHPUnit_Framework_TestCase {

	private $stringPropertyId = 'P572106';

	private $itemPropertyId = 'P1730';

	private function newPropertyDataTypeLookup() {
		$lookup = new InMemoryDataTypeLookup();

		$lookup->setDataTypeForProperty(
			new PropertyId( $this->stringPropertyId ),
			'string-datatype'
		);

		$lookup->setDataTypeForProperty(
			new PropertyId( $this->itemPropertyId ),
			'wikibase-item-datatype'
		);

		return $lookup;
	}

	private function newFormatter() {
		return new OldSnakFormatter(
			$this->newPropertyDataTypeLookup(),
			new TypedValueFormatter(),
			DataTypeFactory::newFromTypes( array(
				$this->newStringDataType(),
				$this->newItemDataType()
		 	) )
		);
	}

	private function newStringDataType() {
		return new DataType(
			'string-datatype',
			'string',
			array(),
			array(),
			array()
		);
	}

	private function newItemDataType() {
		$formatterOptions = new FormatterOptions();
		$parserOptions = new ParserOptions();

		return new DataType(
			'wikibase-item-datatype',
			'wikibase-entityid',
			array( new EntityIdParser( $parserOptions ) ),
			array( new EntityIdFormatter( $formatterOptions ) ),
			array()
		);
	}

	public function testFormatNoSnaks() {
		$formatted = $this->newFormatter()->formatSnaks( array(), 'en' );

		$this->assertInternalType( 'array', $formatted );
		$this->assertEmpty( $formatted );
	}

	private function getStrings() {
		return array(
			'',
			'foo BAR baz!  ',
			'~=[,,_,,]:3'
		);
	}

	public function stringProvider() {
		$argLists = array();

		foreach ( $this->getStrings() as $string ) {
			$argLists[] = array( $string );
		}

		return $argLists;
	}

	/**
	 * @dataProvider stringProvider
	 */
	public function testFormatOnePropertyValueSnakWithString( $expected ) {
		$propertyValueSnak = new PropertyValueSnak(
			new PropertyId( $this->stringPropertyId ),
			new StringValue( $expected )
		);

		$formatted = $this->newFormatter()->formatSnaks( array( $propertyValueSnak ), 'en' );
		$this->assertFormatSnaksReturnType( $formatted );
		$this->assertCount( 1, $formatted );

		$actual = $formatted[0];
		$this->assertEquals( $expected, $actual );
	}

	private function assertFormatSnaksReturnType( $returnValue ) {
		$this->assertInternalType( 'array', $returnValue );
		$this->assertContainsOnly( 'string', $returnValue );
	}

	public function testFormatMultipleValues() {
		$propertyValueSnaks = array();

		foreach ( $this->getStrings() as $string ) {
			$propertyValueSnaks[] = new PropertyValueSnak(
				new PropertyId( $this->stringPropertyId ),
				new StringValue( $string )
			);
		}

		$propertyValueSnaks[] = new PropertyValueSnak(
			new PropertyId( $this->itemPropertyId ),
			new EntityIdValue( new ItemId( 'Q1337' ) )
		);

		$formatted = $this->newFormatter()->formatSnaks( $propertyValueSnaks, 'en' );
		$this->assertFormatSnaksReturnType( $formatted );

		$expected = array_merge(
			$this->getStrings(),
			array( 'Q1337' )
		);

		$this->assertSameSize( $expected, $formatted );
		$this->assertEquals( $expected, $formatted );
	}

}
