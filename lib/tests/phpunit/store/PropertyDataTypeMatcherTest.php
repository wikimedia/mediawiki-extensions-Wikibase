<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\PropertyDataTypeMatcher;

/**
 * @covers Wikibase\Lib\Store\PropertyDataTypeMatcher
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class PropertyDataTypeMatcherTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider propertyDataTypeProvider
	 */
	public function testIsMatchingDataType( $propertyId, $dataType, $expected ) {
		$lookup = $this->getMock( 'Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup' );
		$lookup->expects( $this->once() )
			->method( 'getDataTypeIdForProperty' )
			->with( new PropertyId( 'P1' ) )
			->will( $this->returnValue( 'dataTypeOfP1' ) );

		$instance = new PropertyDataTypeMatcher( $lookup );
		$this->assertSame( $expected, $instance->isMatchingDataType( $propertyId, $dataType ) );
	}

	public function propertyDataTypeProvider() {
		$p1 = new PropertyId( 'P1' );

		return array(
			array( $p1, 'dataTypeOfP1', true ),
			array( $p1, 'otherDataType', false ),
		);
	}

}
