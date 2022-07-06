<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Store\ClientStore;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RedirectResolvingLatestRevisionLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$store = $this->createMock( ClientStore::class );
		$store->expects( $this->once() )
			->method( 'getEntityRevisionLookup' )
			->willReturn( $this->createMock( EntityRevisionLookup::class ) );
		$this->mockService( 'WikibaseClient.Store', $store );

		$this->assertInstanceOf( RedirectResolvingLatestRevisionLookup::class,
			$this->getService( 'WikibaseClient.RedirectResolvingLatestRevisionLookup' ) );
	}

}
