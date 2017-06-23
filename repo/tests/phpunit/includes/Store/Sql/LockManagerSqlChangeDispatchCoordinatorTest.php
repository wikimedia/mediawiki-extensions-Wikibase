<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWiki\MediaWikiServices;
use NullLockManager;
use Wikibase\Repo\Store\Sql\LockManagerSqlChangeDispatchCoordinator;

/**
 * @covers Wikibase\Repo\Store\Sql\LockManagerSqlChangeDispatchCoordinator
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibaseChange
 * @group Database
 *
 * @license GPL-2.0+
 */
class LockManagerSqlChangeDispatchCoordinatorTest extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		$this->tablesUsed[] = 'wb_changes';
		$this->tablesUsed[] = 'wb_changes_dispatch';
	}

	/**
	 * @return LockManagerSqlChangeDispatchCoordinator
	 */
	private function getNullCoordinator() {
		$lockManager = new NullLockManager( [] );
		return new LockManagerSqlChangeDispatchCoordinator(
			$lockManager,
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			false,
			'TestRepo'
		);
	}

	private function resetChangesTable( $id = 23 ) {
		$dbw = wfGetDB( DB_MASTER );

		$row = [
			'change_id' => $id,
			'change_type' => 'test',
			'change_time' => '20140303000000',
			'change_object_id' => '678',
			'change_revision_id' => '6789',
			'change_user_id' => '12345',
			'change_info' => '',
		];

		$dbw->delete( 'wb_changes', '*', __METHOD__ );
		$dbw->insert( 'wb_changes', $row, __METHOD__ );
	}

	public function testBasicLockManager() {
		$this->resetChangesTable();

		$coordinator = $this->getNullCoordinator();
		$coordinator->initState( [ 'foowiki' ] );
		$clientArray = $coordinator->selectClient();
		$this->assertSame( 'foowiki', $clientArray['chd_db'] );
		$this->assertSame( '0', $clientArray['chd_site'] );
		$this->assertSame( '0', $clientArray['chd_seen'] );
		$this->assertNull( $clientArray['chd_lock'] );
	}

}
