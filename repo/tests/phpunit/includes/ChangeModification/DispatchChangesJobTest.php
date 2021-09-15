<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\ChangeModification;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityDiffChangedAspects;
use Wikibase\Lib\Changes\ItemChange;
use Wikibase\Lib\Store\Sql\SqlChangeStore;
use Wikibase\Repo\ChangeModification\DispatchChangesJob;
use Wikibase\Repo\WikibaseRepo;
use WikiMap;

/**
 * @covers \Wikibase\Repo\ChangeModification\DispatchChangesJob
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class DispatchChangesJobTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->tablesUsed[] = 'wb_changes';
		$this->tablesUsed[] = 'wb_changes_subscription';

		$wiki = WikiMap::getCurrentWikiDbDomain()->getId();
		global $wgWBRepoSettings;
		$newRepoSettings = $wgWBRepoSettings;
		$newRepoSettings['localClientDatabases'] = [ $wiki => $wiki ];
		$newRepoSettings['dispatchViaJobsAllowedClients'] = [ $wiki ];
		$newRepoSettings['dispatchViaJobsPruneChangesTableInJobEnabled'] = true;
		$this->setMwGlobals( 'wgWBRepoSettings', $newRepoSettings );
	}

	public function testDispatchJobForSingleChangeToSingleWiki(): void {
		$testItemChange = $this->makeNewChange( 123 );
		$repoDb = WikibaseRepo::getRepoDomainDbFactory()->newRepoDb();
		$changeStore = new SqlChangeStore( $repoDb );
		$changeStore->saveChange( $testItemChange );

		$wiki = WikiMap::getCurrentWikiDbDomain()->getId();
		$dbw = $repoDb->connections()->getWriteConnectionRef();
		$dbw->insert( 'wb_changes_subscription', [
			'cs_entity_id' => 'Q1',
			'cs_subscriber_id' => $wiki,
		] );

		$dispatchChangesJob = DispatchChangesJob::newFromGlobalState( null, [ 'entityId' => 'Q1' ] );

		$dispatchChangesJob->run();

		$jobQueueGroup = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup( $wiki );
		$queuedJobs = $jobQueueGroup->get( 'EntityChangeNotification' )->getAllQueuedJobs();
		$job = $queuedJobs->current();
		$this->assertNotNull( $job );
		$actualItemChangeFields = $job->getParams()['changes'][0];
		$expectedItemChangeFields = $testItemChange->getFields();
		$expectedItemChangeFields['info'] = $testItemChange->getSerializedInfo();
		$this->assertSame( $expectedItemChangeFields, $actualItemChangeFields );
		$this->assertSame( 0, $dbw->selectRowCount( 'wb_changes' ), 'change should be deleted from `wb_changes`' );
	}

	public function testNoValidSubscribers(): void {
		$testItemChange = $this->makeNewChange( 123 );
		$changeStore = new SqlChangeStore(
			WikibaseRepo::getRepoDomainDbFactory()->newRepoDb()
		);
		$changeStore->saveChange( $testItemChange );

		$wiki = WikiMap::getCurrentWikiDbDomain()->getId();
		$dbw = WikibaseRepo::getRepoDomainDbFactory()->newRepoDb()->connections()->getWriteConnectionRef();
		$dbw->insert( 'wb_changes_subscription', [
			'cs_entity_id' => 'Q1',
			'cs_subscriber_id' => 'client',
		] );

		$dispatchChangesJob = DispatchChangesJob::newFromGlobalState( null, [ 'entityId' => 'Q1' ] );

		$dispatchChangesJob->run();

		$jobQueueGroup = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup( $wiki );
		$queuedJobs = $jobQueueGroup->get( 'EntityChangeNotification' )->getAllQueuedJobs();
		$job = $queuedJobs->current();
		$this->assertNull( $job );
	}

	public function testMissingId(): void {
		$wiki = WikiMap::getCurrentWikiDbDomain()->getId();
		$dbw = WikibaseRepo::getRepoDomainDbFactory()->newRepoDb()->connections()->getWriteConnectionRef();
		$dbw->insert( 'wb_changes_subscription', [
			'cs_entity_id' => 'Q1',
			'cs_subscriber_id' => $wiki,
		] );

		$dispatchChangesJob = DispatchChangesJob::newFromGlobalState( null, [ 'entityId' => 'Q1' ] );

		$dispatchChangesJob->run();

		$jobQueueGroup = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup( $wiki );
		$queuedJobs = $jobQueueGroup->get( 'EntityChangeNotification' )->getAllQueuedJobs();
		$job = $queuedJobs->current();
		$this->assertNull( $job );
	}

	public function testRemovingCompletedRows(): void {
		$this->markTestIncomplete( 'Functionality not yet implemented.' );
	}

	private function makeNewChange( int $revisionId ): EntityChange {
		$testItemId = new ItemId( 'Q1' );
		$testItemChange = new ItemChange( [
			'time' => '20210906122813',
			'info' => [
				'compactDiff' => new EntityDiffChangedAspects( [], [], [ 'P1' ], [], false ),
				'metadata' => [
					'page_id' => 3,
					'rev_id' => $revisionId,
					'parent_id' => 4,
					'comment' => '/* wbsetclaim-update:2||1 */ [[Property:P1]]: string on first item: foo 1',
					'user_text' => 'Admin',
					'central_user_id' => 0,
					'bot' => 0,
				],
			],
			'user_id' => '43',
			'revision_id' => (string)$revisionId,
			'object_id' => 'Q1',
			'type' => 'wikibase-item~update',
		] );
		$testItemChange->setEntityId( $testItemId );

		return $testItemChange;
	}
}
