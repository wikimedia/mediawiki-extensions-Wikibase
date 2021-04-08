<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$store = $this->createMock( Store::class );
		$store->expects( $this->once() )
			->method( 'getEntityRevisionLookup' )
			->with( Store::LOOKUP_CACHING_ENABLED )
			->willReturn( $entityRevisionLookup );
		$this->mockService( 'WikibaseRepo.Store',
			$store );

		$this->assertSame( $entityRevisionLookup, $this->getService( 'WikibaseRepo.EntityRevisionLookup' ) );
	}

}
