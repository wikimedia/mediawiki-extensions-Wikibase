<?php

namespace Wikibase\Lib\Test;

use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\Property;

/**
 * @covers Wikibase\Lib\InMemoryDataTypeLookup
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group DataTypeLookupTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class InMemoryDataTypeLookupTest extends \PHPUnit_Framework_TestCase {

	public function testGetDataTypeForPropertyThatIsNotSet() {
		$lookup = new InMemoryDataTypeLookup();

		$this->setExpectedException( '\Wikibase\Lib\PropertyNotFoundException' );

		$lookup->getDataTypeIdForProperty( new EntityId( Property::ENTITY_TYPE, 7201010 ) );
	}

	public function testSetAndGetDataType() {
		$propertyId = new EntityId( Property::ENTITY_TYPE, 7201010 );

		$stringTypeId = 'string-datatype';
		$intTypeId = 'integer';

		$lookup = new InMemoryDataTypeLookup();
		$lookup->setDataTypeForProperty( $propertyId, $stringTypeId );

		$actual = $lookup->getDataTypeIdForProperty( $propertyId );

		$this->assertInternalType( 'string', $actual );

		$this->assertEquals( $stringTypeId, $actual );

		$lookup->setDataTypeForProperty( $propertyId, $intTypeId );

		$actual = $lookup->getDataTypeIdForProperty( $propertyId );

		$this->assertNotEquals( $stringTypeId, $actual );
		$this->assertEquals( $intTypeId, $actual );
	}

	public function testSetWithItemId() {
		$lookup = new InMemoryDataTypeLookup();

		$this->setExpectedException( 'InvalidArgumentException' );

		$lookup->setDataTypeForProperty(
			new EntityId( Item::ENTITY_TYPE, 42 ),
			'string-datatype'
		);
	}

}
