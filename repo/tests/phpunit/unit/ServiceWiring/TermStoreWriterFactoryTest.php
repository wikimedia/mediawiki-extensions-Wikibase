<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Psr\Log\NullLogger;
use WANObjectCache;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\Store\Sql\Terms\TermStoreWriterFactory;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsLookup;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsResolver;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermStoreWriterFactoryTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService( 'WikibaseRepo.LocalEntitySource',
			$this->createMock( DatabaseEntitySource::class ) );
		$this->mockService( 'WikibaseRepo.StringNormalizer',
			new StringNormalizer() );
		$this->mockService( 'WikibaseRepo.TypeIdsAcquirer',
			$this->createMock( TypeIdsAcquirer::class ) );
		$this->mockService( 'WikibaseRepo.TypeIdsLookup',
			$this->createMock( TypeIdsLookup::class ) );
		$this->mockService( 'WikibaseRepo.TypeIdsResolver',
			$this->createMock( TypeIdsResolver::class ) );
		$dbFactory = $this->createStub( RepoDomainDbFactory::class );
		$dbFactory->method( 'newRepoDb' )
			->willReturn( $this->createStub( RepoDomainDb::class ) );
		$this->mockService( 'WikibaseRepo.RepoDomainDbFactory', $dbFactory );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getMainWANObjectCache' )
			->willReturn( $this->createMock( WANObjectCache::class ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getJobQueueGroup' );
		$this->mockService( 'WikibaseRepo.Logger',
			new NullLogger() );

		$this->assertInstanceOf(
			TermStoreWriterFactory::class,
			$this->getService( 'WikibaseRepo.TermStoreWriterFactory' )
		);
	}

}
