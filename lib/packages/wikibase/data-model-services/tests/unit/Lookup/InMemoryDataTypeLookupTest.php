<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;

/**
 * @covers Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class InMemoryDataTypeLookupTest extends \PHPUnit_Framework_TestCase {

	public function testGetDataTypeForPropertyThatIsNotSet() {
		$lookup = new InMemoryDataTypeLookup();

		$this->setExpectedException( 'Wikibase\DataModel\Services\Lookup\PropertyNotFoundException' );

		$lookup->getDataTypeIdForProperty( new PropertyId( 'p7201010' ) );
	}

	public function testSetAndGetDataType() {
		$propertyId = new PropertyId( 'p7201010' );

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

}
