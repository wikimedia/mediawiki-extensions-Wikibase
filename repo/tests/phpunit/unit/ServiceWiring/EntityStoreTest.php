<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityStoreTest extends ServiceWiringTestCase {

	public function testReturnsFromStore(): void {
		$mockStore = $this->createMock( Store::class );
		$mockEntityStore = $this->createMock( EntityStore::class );

		$mockStore->expects( $this->once() )
			->method( 'getEntityStore' )
			->willReturn( $mockEntityStore );

		$this->mockService(
			'WikibaseRepo.Store',
			$mockStore
		);

		$this->assertSame(
			$mockEntityStore,
			$this->getService( 'WikibaseRepo.EntityStore' )
		);
	}

}
