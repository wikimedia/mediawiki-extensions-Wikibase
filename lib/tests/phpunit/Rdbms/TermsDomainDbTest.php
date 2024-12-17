<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Rdbms;

use Wikibase\Lib\Rdbms\ReplicationWaiter;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Rdbms\TermsDomainDb;
use Wikimedia\Rdbms\ConnectionManager;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @covers \Wikibase\Lib\Rdbms\TermsDomainDb
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermsDomainDbTest extends \PHPUnit\Framework\TestCase {

	public function testConnections(): void {
		$expected = $this->createStub( ConnectionManager::class );
		$repoDomainDb = $this->createStub( RepoDomainDb::class );
		$repoDomainDb->method( 'connections' )->willReturn( $expected );

		$this->assertSame( $expected, ( new TermsDomainDb( $repoDomainDb ) )->connections() );
	}

	public function testReplication(): void {
		$expected = $this->createStub( ReplicationWaiter::class );
		$repoDomainDb = $this->createStub( RepoDomainDb::class );
		$repoDomainDb->method( 'replication' )->willReturn( $expected );

		$this->assertSame( $expected, ( new TermsDomainDb( $repoDomainDb ) )->replication() );
	}

	public function testLoadBalancer(): void {
		$expected = $this->createStub( ILoadBalancer::class );
		$repoDomainDb = $this->createStub( RepoDomainDb::class );
		$repoDomainDb->method( 'loadBalancer' )->willReturn( $expected );

		$this->assertSame( $expected, ( new TermsDomainDb( $repoDomainDb ) )->loadBalancer() );
	}

	public function testDomain(): void {
		$expected = 'wikidatawiki';
		$repoDomainDb = $this->createStub( RepoDomainDb::class );
		$repoDomainDb->method( 'domain' )->willReturn( $expected );

		$this->assertSame( $expected, ( new TermsDomainDb( $repoDomainDb ) )->domain() );
	}

}
