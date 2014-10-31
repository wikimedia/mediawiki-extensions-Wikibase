<?php

namespace Wikibase\Test;

use Wikibase\Client\Store\Sql\ConnectionManager;

/**
 * @covers Wikibase\Client\Store\Sql\ConnectionManager
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseClientStore
 *
 * @licence GNU GPL v2+
 * @author DanielKinzler
 */
class ConnectionManagerTest extends \PHPUnit_Framework_TestCase {

	private function getDatabaseBaseMock() {
		$db = $this->getMockBuilder( 'DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		return $db;
	}

	private function getLoadBalancerMock() {
		$lb = $this->getMockBuilder( 'LoadBalancer' )
			->disableOriginalConstructor()
			->getMock();

		return $lb;
	}

	public function testGetReadConnection() {
		$db = $this->getDatabaseBaseMock();
		$lb = $this->getLoadBalancerMock();

		$lb->expects( $this->once() )
			->method( 'getConnection' )
			->with( DB_READ )
			->willReturn( $db );

		$manager = new ConnectionManager( $lb );
		$actual = $manager->getReadConnection();

		$this->assertSame( $db, $actual );
	}

	public function testReleaseConnection() {
		$db = $this->getDatabaseBaseMock();
		$lb = $this->getLoadBalancerMock();

		$lb->expects( $this->once() )
			->method( 'reuseConnection' )
			->with( $db )
			->willReturn( null );

		$manager = new ConnectionManager( $lb );
		$manager->releaseConnection( $db );
	}

	public function testBeginAtomicSection() {
		$db = $this->getDatabaseBaseMock();
		$lb = $this->getLoadBalancerMock( );

		$lb->expects( $this->once() )
			->method( 'getConnection' )
			->with( DB_WRITE )
			->willReturn( $db );

		$db->expects( $this->once() )
			->method( 'startAtomic' )
			->withAnyParameters()
			->willReturn( null );

		$manager = new ConnectionManager( $lb );
		$manager->beginAtomicSection( 'TEST' );
	}

	public function testCommitAtomicSection() {
		$db = $this->getDatabaseBaseMock();
		$lb = $this->getLoadBalancerMock( );

		$lb->expects( $this->once() )
			->method( 'reuseConnection' )
			->with( $db )
			->willReturn( null );

		$db->expects( $this->once() )
			->method( 'endAtomic' )
			->withAnyParameters()
			->willReturn( null );

		$manager = new ConnectionManager( $lb );
		$manager->commitAtomicSection( $db, 'TEST' );
	}

	public function testRollbackAtomicSection() {
		$db = $this->getDatabaseBaseMock();
		$lb = $this->getLoadBalancerMock( );

		$lb->expects( $this->once() )
			->method( 'reuseConnection' )
			->with( $db )
			->willReturn( null );

		$db->expects( $this->once() )
			->method( 'rollback' )
			->withAnyParameters()
			->willReturn( null );

		$manager = new ConnectionManager( $lb );
		$manager->rollbackAtomicSection( $db, 'TEST' );
	}

}
