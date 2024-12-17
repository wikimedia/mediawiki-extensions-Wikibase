<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\Rdbms\TermsDomainDb;
use Wikibase\Lib\Rdbms\TermsDomainDbFactory;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikimedia\ObjectCache\WANObjectCache;

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

		$dbFactory = $this->createStub( TermsDomainDbFactory::class );
		$dbFactory->method( 'newTermsDb' )
			->willReturn( $this->createStub( TermsDomainDb::class ) );
		$this->mockService( 'WikibaseRepo.TermsDomainDbFactory', $dbFactory );

		$databaseTypeIdsStore = $this->getService( 'WikibaseRepo.DatabaseTypeIdsStore' );

		$this->assertInstanceOf( DatabaseTypeIdsStore::class, $databaseTypeIdsStore );
	}

}
