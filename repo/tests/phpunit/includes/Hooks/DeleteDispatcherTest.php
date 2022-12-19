<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\Hooks;

use IJobSpecification;
use JobQueueGroup;
use ManualLogEntry;
use PHPUnit\Framework\TestCase;
use Title;
use User;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Repo\Hooks\DeleteDispatcher;
use WikiPage;

/**
 * @covers \Wikibase\Repo\Hooks\DeleteDispatcher
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DeleteDispatcherTest extends TestCase {

	private $localClientDatabases = [ 'asdfwiki', 'somewiki' ];

	public function earlyAbortProvider() {
		$title = Title::makeTitle( NS_MAIN, 'Some_cool_page' );
		$wikiPage = $this->createMock( WikiPage::class );
		$wikiPage->method( 'getTitle' )
			->willReturn( $title );

		$entityIdLookup = $this->createMock( EntityIdLookup::class );
		$entityIdLookup->expects( $this->once() )
			->method( 'getEntityIdForTitle' )
			->with( $title )
			->willReturn( null );

		$entityIdLookupNotCalled = $this->newEntityIdLookupNeverCalled();

		return [
			'no client databases' => [ 1, [], $wikiPage, $entityIdLookupNotCalled ],
			'no archive records' => [ 0,  $this->localClientDatabases, $wikiPage, $entityIdLookupNotCalled ],
			'not an entity page' => [ 1, $this->localClientDatabases, $wikiPage, $entityIdLookup ],
		];
	}

	/**
	 * @dataProvider earlyAbortProvider
	 * @param int $archivedRecordsCount
	 * @param string[] $clients
	 * @param WikiPage $wikiPage
	 * @param EntityIdLookup $entityIdLookup
	 */
	public function testShouldNotSpawnDeleteDispatchJobAndAbortEarly(
		int $archivedRecordsCount,
		array $clients,
		WikiPage $wikiPage,
		EntityIdLookup $entityIdLookup
	) {
		$deleteDispatcher = new DeleteDispatcher(
			$this->newJobQueueGroupNeverCalled(),
			$entityIdLookup,
			$clients
		);
		$status = $deleteDispatcher->onArticleDeleteComplete(
			$wikiPage,
			$this->createMock( User::class ),
			"reason",
			1,
			null,
			$this->createMock( ManualLogEntry::class ),
			$archivedRecordsCount
		);

		$this->assertTrue( $status );
	}

	public function testShouldSpawnDeleteDispatchNotificationJobs() {
		$id = 1;
		$archivedRevisionCount = 1234;
		$entityId = new ItemId( 'Q123' );
		$title = Title::newFromTextThrow( $entityId->getSerialization() );
		$expectedJobParams = [ "pageId" => $id, "archivedRevisionCount" => $archivedRevisionCount ];

		$jobQueueGroup = $this->newJobQueueGroup( $expectedJobParams );

		$entityIdLookup = $this->createMock( EntityIdLookup::class );
		$entityIdLookup->expects( $this->once() )
			->method( 'getEntityIdForTitle' )
			->with( $title )
			->willReturn( $entityId );

		$deleteDispatcher = new DeleteDispatcher( $jobQueueGroup, $entityIdLookup, $this->localClientDatabases );

		$wikiPage = $this->createMock( WikiPage::class );

		$wikiPage->method( 'getTitle' )
			->willReturn( $title );

		$user = $this->createMock( User::class );
		$logEntry = $this->createMock( ManualLogEntry::class );

		$deleteDispatcher->onArticleDeleteComplete(
			$wikiPage,
			$user,
			"reason",
			$id,
			null,
			$logEntry,
			$archivedRevisionCount
		);
	}

	private function newEntityIdLookupNeverCalled() {
		$entityIdLookup = $this->createMock( EntityIdLookup::class );
		$entityIdLookup->expects( $this->never() )
			->method( 'getEntityIdForTitle' );
		return $entityIdLookup;
	}

	private function newJobQueueGroupNeverCalled() {
		$jobQueueGroup = $this->createMock( JobQueueGroup::class );
		$jobQueueGroup->expects( $this->never() )
			->method( 'push' );
		return $jobQueueGroup;
	}

	private function newJobQueueGroup(
		array $expectedJobParams
		): JobQueueGroup {
		$jobQueueGroup = $this->createMock( JobQueueGroup::class );
		$jobQueueGroup->expects( $this->once() )
			->method( 'push' )
			->willReturnCallback( function ( IJobSpecification $job ) use ( $expectedJobParams ) {
				$this->assertSame( 'DispatchChangeDeletionNotification', $job->getType() );
				$this->assertSame( $expectedJobParams['pageId'], $job->getParams()['pageId'] );
				$this->assertSame( $expectedJobParams['archivedRevisionCount'], $job->getParams()['archivedRevisionCount'] );
			} );

		return $jobQueueGroup;
	}
}
