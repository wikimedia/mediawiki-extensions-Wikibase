<?php

namespace Wikibase\Test;

use Wikibase\Client\Store\Sql\ConnectionManager;

/**
 * @covers Wikibase\Client\Store\Sql\ConnectionManagerTest
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseClientStore
 *
 * @licence GNU GPL v2+
 * @author DanielKinzler
 */
class ConnectionManagerTest extends \PHPUnit_Framework_TestCase {

	private function getConnectionMock() {
		$connection = $this->getMockBuilder( 'DatabaseMySQL' )
			->disableOriginalConstructor()
			->getMock();

		return $connection;
	}

	private function getLoadBalancerMock() {
		$lb = $this->getMockBuilder( 'LoadBalancer' )
			->disableOriginalConstructor()
			->getMock();

		return $lb;
	}

	public function testGetReadConnection() {
		$connection = $this->getConnectionMock();
		$lb = $this->getLoadBalancerMock();

		$lb->expects( $this->once() )
			->method( 'getConnection' )
			->with( DB_READ )
			->will( $this->returnValue( $connection ) );

		$manager = new ConnectionManager( $lb );
		$actual = $manager->getReadConnection();

		$this->assertSame( $connection, $actual );
	}

	public function testReleaseConnection() {
		$connection = $this->getConnectionMock();
		$lb = $this->getLoadBalancerMock();

		$lb->expects( $this->once() )
			->method( 'reuseConnection' )
			->with( $connection )
			->will( $this->returnValue( null ) );

		$manager = new ConnectionManager( $lb );
		$manager->releaseConnection( $connection );
	}

	public function testBeginAtomicSection() {
		$connection = $this->getConnectionMock();
		$lb = $this->getLoadBalancerMock( );

		$lb->expects( $this->once() )
			->method( 'getConnection' )
			->with( DB_WRITE )
			->will( $this->returnValue( $connection ) );

		$connection->expects( $this->once() )
			->method( 'startAtomic' )
			->withAnyParameters()
			->will( $this->returnValue( null ) );

		$manager = new ConnectionManager( $lb );
		$manager->beginAtomicSection();
	}

	public function testCommitAtomicSection() {
		$connection = $this->getConnectionMock();
		$lb = $this->getLoadBalancerMock( );

		$lb->expects( $this->once() )
			->method( 'reuseConnection' )
			->with( $connection )
			->will( $this->returnValue( null ) );

		$connection->expects( $this->once() )
			->method( 'endAtomic' )
			->withAnyParameters()
			->will( $this->returnValue( null ) );

		$manager = new ConnectionManager( $lb );
		$manager->commitAtomicSection( $connection );
	}

	public function testRollbackAtomicSection() {
		$connection = $this->getConnectionMock();
		$lb = $this->getLoadBalancerMock( );

		$lb->expects( $this->once() )
			->method( 'reuseConnection' )
			->with( $connection )
			->will( $this->returnValue( null ) );

		$connection->expects( $this->once() )
			->method( 'rollback' )
			->withAnyParameters()
			->will( $this->returnValue( null ) );

		$manager = new ConnectionManager( $lb );
		$manager->rollbackAtomicSection( $connection );
	}

}
