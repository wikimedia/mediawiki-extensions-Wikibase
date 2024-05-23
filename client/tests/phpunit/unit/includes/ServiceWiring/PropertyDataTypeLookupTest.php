<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Psr\Log\NullLogger;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
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
		$this->mockService( 'WikibaseClient.PropertyInfoLookup',
			$this->createMock( PropertyInfoLookup::class ) );
		$this->mockService( 'WikibaseClient.Logger',
			new NullLogger() );
		// WikibaseClient.EntityLookup is only used lazily and so not mocked

		$this->assertInstanceOf(
			PropertyDataTypeLookup::class,
			$this->getService( 'WikibaseClient.PropertyDataTypeLookup' )
		);
	}

}
