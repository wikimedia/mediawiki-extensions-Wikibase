<?php

namespace Wikibase\Client\Tests\Store\Sql;

use IDatabase;
use LoadBalancer;
use PHPUnit_Framework_MockObject_MockObject;
use Wikibase\Client\Store\Sql\ConsistentReadConnectionManager;

/**
 * @covers Wikibase\Client\Store\Sql\ConsistentReadConnectionManager
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseClientStore
 *
 * @license GPL-2.0+
 * @author DanielKinzler
 */
class ConsistentReadConnectionManagerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return IDatabase|PHPUnit_Framework_MockObject_MockObject
	 */
	private function getConnectionMock() {
		return $this->getMock( IDatabase::class );
	}

	/**
	 * @return LoadBalancer|PHPUnit_Framework_MockObject_MockObject
	 */
	private function getLoadBalancerMock() {
		$lb = $this->getMockBuilder( LoadBalancer::class )
			->disableOriginalConstructor()
			->getMock();

		return $lb;
	}

	public function testGetReadConnection() {
		$connection = $this->getConnectionMock();
		$lb = $this->getLoadBalancerMock();

		$lb->expects( $this->once() )
			->method( 'getConnection' )
			->with( DB_SLAVE )
			->will( $this->returnValue( $connection ) );

		$manager = new ConsistentReadConnectionManager( $lb );
		$actual = $manager->getReadConnection();

		$this->assertSame( $connection, $actual );
	}

	public function testGetWriteConnection() {
		$connection = $this->getConnectionMock();
		$lb = $this->getLoadBalancerMock();

		$lb->expects( $this->once() )
			->method( 'getConnection' )
			->with( DB_MASTER )
			->will( $this->returnValue( $connection ) );

		$manager = new ConsistentReadConnectionManager( $lb );
		$actual = $manager->getWriteConnection();

		$this->assertSame( $connection, $actual );
	}

	public function testForceMaster() {
		$connection = $this->getConnectionMock();
		$lb = $this->getLoadBalancerMock();

		$lb->expects( $this->once() )
			->method( 'getConnection' )
			->with( DB_MASTER )
			->will( $this->returnValue( $connection ) );

		$manager = new ConsistentReadConnectionManager( $lb );
		$manager->forceMaster();
		$manager->getReadConnection();
	}

	public function testReleaseConnection() {
		$connection = $this->getConnectionMock();
		$lb = $this->getLoadBalancerMock();

		$lb->expects( $this->once() )
			->method( 'reuseConnection' )
			->with( $connection )
			->will( $this->returnValue( null ) );

		$manager = new ConsistentReadConnectionManager( $lb );
		$manager->releaseConnection( $connection );
	}

	public function testBeginAtomicSection() {
		$connection = $this->getConnectionMock();
		$lb = $this->getLoadBalancerMock();

		$lb->expects( $this->exactly( 2 ) )
			->method( 'getConnection' )
			->with( DB_MASTER )
			->will( $this->returnValue( $connection ) );

		$connection->expects( $this->once() )
			->method( 'startAtomic' )
			->will( $this->returnValue( null ) );

		$manager = new ConsistentReadConnectionManager( $lb );
		$manager->beginAtomicSection( 'TEST' );

		// Should also ask for a DB_MASTER connection.
		// This is asserted by the $lb mock.
		$manager->getReadConnection();
	}

	public function testCommitAtomicSection() {
		$connection = $this->getConnectionMock();
		$lb = $this->getLoadBalancerMock();

		$lb->expects( $this->once() )
			->method( 'reuseConnection' )
			->with( $connection )
			->will( $this->returnValue( null ) );

		$connection->expects( $this->once() )
			->method( 'endAtomic' )
			->will( $this->returnValue( null ) );

		$manager = new ConsistentReadConnectionManager( $lb );
		$manager->commitAtomicSection( $connection, 'TEST' );
	}

	public function testRollbackAtomicSection() {
		$connection = $this->getConnectionMock();
		$lb = $this->getLoadBalancerMock();

		$lb->expects( $this->once() )
			->method( 'reuseConnection' )
			->with( $connection )
			->will( $this->returnValue( null ) );

		$connection->expects( $this->once() )
			->method( 'rollback' )
			->will( $this->returnValue( null ) );

		$manager = new ConsistentReadConnectionManager( $lb );
		$manager->rollbackAtomicSection( $connection, 'TEST' );
	}

}
