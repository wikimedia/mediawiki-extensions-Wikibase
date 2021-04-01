<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityStoreWatcherTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$entityStoreWatcher = $this->createMock( EntityStoreWatcher::class );
		$store = $this->createMock( Store::class );
		$store->expects( $this->once() )
			->method( 'getEntityStoreWatcher' )
			->willReturn( $entityStoreWatcher );
		$this->mockService( 'WikibaseRepo.Store',
			$store );

		$this->assertSame(
			$entityStoreWatcher,
			$this->getService( 'WikibaseRepo.EntityStoreWatcher' )
		);
	}

}
