<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Psr\Log\NullLogger;
use Wikibase\Client\Store\ClientStore;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyDataTypeLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$propertyInfoLookup = $this->createMock( PropertyInfoLookup::class );
		$store = $this->createMock( ClientStore::class );
		$store->expects( $this->once() )
			->method( 'getPropertyInfoLookup' )
			->willReturn( $propertyInfoLookup );
		$this->mockService( 'WikibaseClient.Store',
			$store );
		$this->mockService( 'WikibaseClient.EntityLookup',
			$this->createMock( EntityLookup::class ) );
		$this->mockService( 'WikibaseClient.Logger',
			new NullLogger() );

		$this->assertInstanceOf(
			PropertyDataTypeLookup::class,
			$this->getService( 'WikibaseClient.PropertyDataTypeLookup' )
		);
	}

}
