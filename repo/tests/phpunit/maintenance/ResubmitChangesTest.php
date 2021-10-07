<?php

namespace Wikibase\Repo\Tests\Maintenance;

use MediaWiki\MediaWikiServices;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use Wikibase\Lib\Changes\ChangeRow;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Repo\Maintenance\ResubmitChanges;
use Wikibase\Repo\WikibaseRepo;
use WikiMap;
use Wikimedia\Timestamp\ConvertibleTimestamp;

// files in maintenance/ are not autoloaded to avoid accidental usage, so load explicitly
require_once __DIR__ . '/../../../maintenance/ResubmitChanges.php';

/**
 * @covers \Wikibase\Repo\Maintenance\ResubmitChanges
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class ResubmitChangesTest extends MaintenanceBaseTestCase {

	protected function getMaintenanceClass() {
		return ResubmitChanges::class;
	}

	protected function setUp(): void {
		parent::setUp();

		$this->tablesUsed[] = 'wb_changes';

		$this->db->delete( 'wb_changes', '*', __METHOD__ );
	}

	public function testExecute() {
		$this->storeNewChanges();

		$this->maintenance->loadWithArgv( [ '--minimum-age', 60 * 5 ] );
		$this->maintenance->execute();

		$wiki = WikiMap::getCurrentWikiDbDomain()->getId();
		$jobQueueGroup = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup( $wiki );
		$queuedJobs = $jobQueueGroup->get( 'DispatchChanges' )->getAllQueuedJobs();

		$job = $queuedJobs->current();
		$this->assertNotNull( $job );
		$actualEntityId = $job->getParams()['entityId'];
		$this->assertSame( 'Q1', $actualEntityId );

		$queuedJobs->next();
		$nextJob = $queuedJobs->current();
		$this->assertNull( $nextJob );
	}

	private function storeNewChanges() {
		$store = WikibaseRepo::getStore()->getChangeStore();

		$oldChange = new EntityChange( [
			ChangeRow::TYPE => 'wikibase-item~' . EntityChange::ADD,
			ChangeRow::OBJECT_ID => 'Q1',
		] );
		$oldChange->setTimestamp( ConvertibleTimestamp::convert( TS_MW, time() - 60 * 15 ) );
		$store->saveChange( $oldChange );

		$newChange = new EntityChange( [
			ChangeRow::TYPE => 'wikibase-item~' . EntityChange::ADD,
			ChangeRow::OBJECT_ID => 'Q2',
		] );
		$newChange->setTimestamp( ConvertibleTimestamp::convert( TS_MW, time() - 5 ) );
		$store->saveChange( $newChange );
	}
}
