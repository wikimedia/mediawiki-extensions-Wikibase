<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use WANObjectCache;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DatabaseTypeIdsStoreTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->serviceContainer->expects( $this->once() )
			->method( 'getMainWANObjectCache' )
			->willReturn( $this->createMock( WANObjectCache::class ) );

		$dbFactory = $this->createStub( RepoDomainDbFactory::class );
		$dbFactory->method( 'newRepoDb' )
			->willReturn( $this->createStub( RepoDomainDb::class ) );
		$this->mockService( 'WikibaseRepo.RepoDomainDbFactory', $dbFactory );

		$databaseTypeIdsStore = $this->getService( 'WikibaseRepo.DatabaseTypeIdsStore' );

		$this->assertInstanceOf( DatabaseTypeIdsStore::class, $databaseTypeIdsStore );
	}

}
