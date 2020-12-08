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
use Wikibase\Repo\Content\EntityContentFactory;
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
		$title = Title::newFromTextThrow( 'some cool page' );
		$wikiPage = $this->createMock( WikiPage::class );
		$wikiPage->method( 'getTitle' )
			->willReturn( $title );

		$entityContentFactory = $this->createMock( EntityContentFactory::class );
		$entityContentFactory->expects( $this->once() )
			->method( 'getEntityIdForTitle' )
			->with( $title )
			->willReturn( null );

		$entityContentFactoryNotCalled = $this->newEntityContentFactoryNeverCalled();

		$wikiPageNoTitle = $this->createMock( WikiPage::class );
		$wikiPageNoTitle->method( 'getTitle' )
			->willReturn( null );

		return [
			'no client databases' => [ 1, [], $wikiPage, $entityContentFactoryNotCalled ],
			'no archive records' => [ 0,  $this->localClientDatabases, $wikiPage, $entityContentFactoryNotCalled ],
			'wiki-page has no title' => [ 1, $this->localClientDatabases, $wikiPageNoTitle, $entityContentFactoryNotCalled ],
			'not an entity page' => [ 1, $this->localClientDatabases, $wikiPage, $entityContentFactory ]
		];
	}

	/**
	 * @dataProvider earlyAbortProvider
	 * @param int $archivedRecordsCount
	 * @param string[] $clients
	 * @param WikiPage $wikiPage
	 * @param EntityContentFactory $entityContentFactory
	 */
	public function testShouldNotSpawnDeleteDispatchJobAndAbortEarly(
		int $archivedRecordsCount,
		array $clients,
		WikiPage $wikiPage,
		EntityContentFactory $entityContentFactory
	) {
		$deleteDispatcher = new DeleteDispatcher(
			$this->newJobQueueGroupFactoryNeverCalled(),
			$entityContentFactory,
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

		$factory = $this->newJobQueueGroupFactory( $expectedJobParams );

		$entityContentFactory = $this->createMock( EntityContentFactory::class );
		$entityContentFactory->expects( $this->once() )
			->method( 'getEntityIdForTitle' )
			->with( $title )
			->willReturn( $entityId );

		$deleteDispatcher = new DeleteDispatcher( $factory, $entityContentFactory, $this->localClientDatabases );

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

	private function newEntityContentFactoryNeverCalled() {
		$entityContentFactory = $this->createMock( EntityContentFactory::class );
		$entityContentFactory->expects( $this->never() )
			->method( 'getEntityIdForTitle' );
		return $entityContentFactory;
	}

	private function newJobQueueGroupFactoryNeverCalled() {
		return function() {
			$jobQueueGroup = $this->createMock( JobQueueGroup::class );
			$jobQueueGroup->expects( $this->never() )
				->method( 'push' );
			return $jobQueueGroup;
		};
	}

	private function newJobQueueGroupFactory(
		array $expectedJobParams
	): callable {
		return function ( string $wikiId ) use ( $expectedJobParams ) {
			$jobQueueGroup = $this->createMock( JobQueueGroup::class );
			$jobQueueGroup->expects( $this->once() )
				->method( 'push' )
				->willReturnCallback( function ( IJobSpecification $job ) use ( $expectedJobParams ) {
					$this->assertSame( 'DispatchChangeDeletionNotification', $job->getType() );
					$this->assertSame( $expectedJobParams['pageId'], $job->getParams()['pageId'] );
					$this->assertSame( $expectedJobParams['archivedRevisionCount'], $job->getParams()['archivedRevisionCount'] );
				} );

			return $jobQueueGroup;
		};
	}
}
