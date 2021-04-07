<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$entityLookup = $this->createMock( EntityLookup::class );
		$store = $this->createMock( Store::class );
		$store->expects( $this->once() )
			->method( 'getEntityLookup' )
			->with( Store::LOOKUP_CACHING_ENABLED, LookupConstants::LATEST_FROM_REPLICA )
			->willReturn( $entityLookup );
		$this->mockService( 'WikibaseRepo.Store',
			$store );

		$this->assertSame( $entityLookup, $this->getService( 'WikibaseRepo.EntityLookup' ) );
	}

}
