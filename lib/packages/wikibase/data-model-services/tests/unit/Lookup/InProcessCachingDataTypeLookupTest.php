<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\InProcessCachingDataTypeLookup;

/**
 * @covers Wikibase\DataModel\Services\Lookup\InProcessCachingDataTypeLookup
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class InProcessCachingDataTypeLookupTest extends PHPUnit_Framework_TestCase {

	public function testWhenCacheIsEmpty_decoratedLookupValueIsReturned() {
		$decoratedLookup = $this->getMock( 'Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup' );

		$decoratedLookup->expects( $this->once() )
			->method( 'getDataTypeIdForProperty' )
			->with( new PropertyId( 'P1' ) )
			->will( $this->returnValue( 'string' ) );

		$cachingLookup = new InProcessCachingDataTypeLookup( $decoratedLookup );

		$this->assertSame(
			'string',
			$cachingLookup->getDataTypeIdForProperty( new PropertyId( 'P1' ) )
		);
	}

	public function testWhenValueInCache_cacheValueIsReturned() {
		$decoratedLookup = $this->getMock( 'Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup' );

		$decoratedLookup->expects( $this->once() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( 'string' ) );

		$cachingLookup = new InProcessCachingDataTypeLookup( $decoratedLookup );
		$cachingLookup->getDataTypeIdForProperty( new PropertyId( 'P1' ) );

		$decoratedLookup->expects( $this->never() )
			->method( 'getDataTypeIdForProperty' );

		$this->assertSame(
			'string',
			$cachingLookup->getDataTypeIdForProperty( new PropertyId( 'P1' ) )
		);
	}

}
