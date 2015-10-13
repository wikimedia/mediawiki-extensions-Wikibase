<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\InProcessCachingDataTypeLookup;

/**
 * @covers Wikibase\DataModel\Services\Lookup\InProcessCachingDataTypeLookup
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class InProcessCachingDataTypeLookupTest extends PHPUnit_Framework_TestCase {

	public function testIsMatchingDataTypeInProcessCaching() {
		$propertyDataTypeLookup = $this->getMock(
			'Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup'
		);

		$propertyDataTypeLookup->expects( $this->once() )
			->method( 'getDataTypeIdForProperty' )
			->with( new PropertyId( 'P1' ) )
			->will( $this->returnValue( 'string' ) );

		$cachingDataTypeLookup = new InProcessCachingDataTypeLookup( $propertyDataTypeLookup );

		$dataType = $cachingDataTypeLookup->getDataTypeIdForProperty( new PropertyId( 'P1' ) );
		$dataType = $cachingDataTypeLookup->getDataTypeIdForProperty( new PropertyId( 'P1' ) );

		$this->assertEquals(
			'string',
			$dataType,
			'P1 has string data type and non-caching PropertyDataTypeLookup invoked only once.'
		);
	}

}
