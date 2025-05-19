<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\Hooks;

use MediaWiki\JobQueue\IJobSpecification;
use MediaWiki\JobQueue\JobQueueGroup;
use MediaWiki\Logging\ManualLogEntry;
use MediaWiki\Page\PageIdentityValue;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Repo\Hooks\DeleteDispatcher;

/**
 * @covers \Wikibase\Repo\Hooks\DeleteDispatcher
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DeleteDispatcherTest extends TestCase {

	private const LOCAL_CLIENT_DATABASES = [ 'asdfwiki', 'somewiki' ];

	public static function earlyAbortProvider(): iterable {
		$title = Title::makeTitle( NS_MAIN, 'Some_cool_page' );

		$entityIdLookupFactory = function ( self $self ) use ( $title ) {
			$lookup = $self->createMock( EntityIdLookup::class );
			$lookup->expects( self::once() )
				->method( 'getEntityIdForTitle' )
				->with( $title )
				->willReturn( null );
			return $lookup;
		};

		$entityIdLookupNotCalledFactory = fn ( self $self ) => $self->newEntityIdLookupNeverCalled();

		return [
			'no client databases' => [ 1, [], $title, $entityIdLookupNotCalledFactory ],
			'no archive records' => [ 0, self::LOCAL_CLIENT_DATABASES, $title, $entityIdLookupNotCalledFactory ],
			'not an entity page' => [ 1, self::LOCAL_CLIENT_DATABASES, $title, $entityIdLookupFactory ],
		];
	}

	/**
	 * @dataProvider earlyAbortProvider
	 * @param int $archivedRecordsCount
	 * @param string[] $clients
	 * @param Title $title
	 * @param callable $entityIdLookupFactory
	 */
	public function testShouldNotSpawnDeleteDispatchJobAndAbortEarly(
		int $archivedRecordsCount,
		array $clients,
		Title $title,
		callable $entityIdLookupFactory
	) {
		$entityIdLookup = $entityIdLookupFactory( $this );

		$titleFactory = $this->createConfiguredMock( TitleFactory::class, [
			'newFromPageIdentity' => $title,
		] );

		$deleteDispatcher = new DeleteDispatcher(
			$this->newJobQueueGroupNeverCalled(),
			$titleFactory,
			$entityIdLookup,
			$clients
		);
		$status = $deleteDispatcher->onPageDeleteComplete(
			new PageIdentityValue( 1, 0, "nothing", false ),
			$this->createMock( User::class ),
			"reason",
			1,
			$this->createMock( RevisionRecord::class ),
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

		$titleFactory = $this->createConfiguredMock( TitleFactory::class, [
			'newFromPageIdentity' => $title,
		] );

		$deleteDispatcher = new DeleteDispatcher(
			$jobQueueGroup,
			$titleFactory,
			$entityIdLookup,
			self::LOCAL_CLIENT_DATABASES
		);

		$user = $this->createMock( User::class );
		$logEntry = $this->createMock( ManualLogEntry::class );

		$deleteDispatcher->onPageDeleteComplete(
			new PageIdentityValue( 1, 0, "nothing", false ),
			$user,
			"reason",
			$id,
			$this->createMock( RevisionRecord::class ),
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
