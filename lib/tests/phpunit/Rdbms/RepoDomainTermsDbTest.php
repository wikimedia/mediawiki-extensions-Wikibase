<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Rdbms;

use Wikibase\Lib\Rdbms\ReplicationWaiter;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Rdbms\RepoDomainTermsDb;
use Wikimedia\Rdbms\ConnectionManager;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @covers \Wikibase\Lib\Rdbms\RepoDomainTermsDb
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RepoDomainTermsDbTest extends \PHPUnit\Framework\TestCase {

	public function testGetReadConnection(): void {
		$loadGroups = [ 'some group' ];
		$expected = $this->createStub( IDatabase::class );

		$connectionManager = $this->createMock( ConnectionManager::class );
		$connectionManager->expects( $this->once() )
			->method( 'getReadConnection' )
			->with( $loadGroups )
			->willReturn( $expected );

		$repoDomainDb = $this->createStub( RepoDomainDb::class );
		$repoDomainDb->method( 'connections' )->willReturn( $connectionManager );

		$this->assertSame(
			$expected,
			( new RepoDomainTermsDb( $repoDomainDb ) )->getReadConnection( $loadGroups )
		);
	}

	public function testGetWriteConnection(): void {
		$expected = $this->createStub( IDatabase::class );

		$connectionManager = $this->createMock( ConnectionManager::class );
		$connectionManager->expects( $this->once() )
			->method( 'getWriteConnection' )
			->willReturn( $expected );

		$repoDomainDb = $this->createStub( RepoDomainDb::class );
		$repoDomainDb->method( 'connections' )->willReturn( $connectionManager );

		$this->assertSame(
			$expected,
			( new RepoDomainTermsDb( $repoDomainDb ) )->getWriteConnection()
		);
	}

	public function testWaitForReplication(): void {
		$timeout = 42;
		$replicationWaiter = $this->createMock( ReplicationWaiter::class );
		$replicationWaiter->expects( $this->once() )
			->method( 'waitForAllAffectedClusters' )
			->with( $timeout );

		$repoDomainDb = $this->createStub( RepoDomainDb::class );
		$repoDomainDb->method( 'replication' )->willReturn( $replicationWaiter );

		( new RepoDomainTermsDb( $repoDomainDb ) )->waitForReplicationOfAllAffectedClusters( $timeout );
	}

	public function testLoadBalancer(): void {
		$expected = $this->createStub( ILoadBalancer::class );
		$repoDomainDb = $this->createStub( RepoDomainDb::class );
		$repoDomainDb->method( 'loadBalancer' )->willReturn( $expected );

		$this->assertSame( $expected, ( new RepoDomainTermsDb( $repoDomainDb ) )->loadBalancer() );
	}

	public function testDomain(): void {
		$expected = 'wikidatawiki';
		$repoDomainDb = $this->createStub( RepoDomainDb::class );
		$repoDomainDb->method( 'domain' )->willReturn( $expected );

		$this->assertSame( $expected, ( new RepoDomainTermsDb( $repoDomainDb ) )->domain() );
	}

}
