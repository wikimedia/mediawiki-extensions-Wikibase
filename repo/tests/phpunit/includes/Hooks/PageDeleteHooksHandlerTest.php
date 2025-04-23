<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Hooks;

use MediaWiki\Content\Content;
use MediaWiki\Content\WikitextContent;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Content\EntityInstanceHolder;
use Wikibase\Repo\Content\ItemContent;
use Wikibase\Repo\Hooks\PageDeleteHooksHandler;
use Wikibase\Repo\Notifications\ChangeNotifier;

/**
 * @covers \Wikibase\Repo\Hooks\PageDeleteHooksHandler
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class PageDeleteHooksHandlerTest extends TestCase {

	private PageDeleteHooksHandler $handler;
	private MockObject $changeNotifier;
	private MockObject $entityStoreWatcher;
	private MockObject $mockAuthority;

	protected function setUp(): void {
		$this->mockAuthority = $this->createMock( Authority::class );
		$this->entityStoreWatcher = $this->createMock( EntityStoreWatcher::class );
		$this->changeNotifier = $this->createMock( ChangeNotifier::class );

		$this->handler = new PageDeleteHooksHandler(
			$this->changeNotifier,
			new EntityContentFactory( [ 'wikibase-item' ], [] ),
			$this->entityStoreWatcher
		);
	}

	public static function provideRevisionContent(): iterable {
		$itemId = new ItemId( 'Q1111' );
		yield 'deleted page is an entity' => [
			new ItemContent( new EntityInstanceHolder( new Item( $itemId ) ) ),
			true,
			$itemId,
		];

		yield 'deleted page is not an entity' => [
			new WikitextContent( 'is a page not an entity' ),
			false,
		];
	}

	/**
	 * @dataProvider provideRevisionContent
	 */
	public function testOnPageDeleteComplete(
		Content $content,
		bool $methodsShouldBeCalled,
		?EntityId $entityId = null
	) {
		$deletedRev = $this->createMock( RevisionRecord::class );
		$deletedRev->method( 'getMainContentRaw' )->willReturn( $content );

		$this->entityStoreWatcher->expects( $methodsShouldBeCalled ? $this->once() : $this->never() )
			->method( 'entityDeleted' )
			->with( $entityId );

		$this->changeNotifier->expects( $methodsShouldBeCalled ? $this->once() : $this->never() )
			->method( 'notifyOnPageDeleted' )
			->with( $content, $this->mockAuthority->getUser() );

		// Only $deleter, $deletedRev, and $logEntry are used by this function, so all other arguments are arbitrary
		$this->handler->onPageDeleteComplete(
			$this->createMock( ProperPageIdentity::class ),
			$this->mockAuthority,
			'deleting for testOnPageDeleteComplete',
			123,
			$deletedRev,
			$this->createMock( \ManualLogEntry::class ),
			22,
		);
	}

	/**
	 * @dataProvider provideRevisionContent
	 */
	public function testOnPageUndeleteComplete( Content $content, bool $methodsShouldBeCalled ) {
		$restoredRev = $this->createMock( RevisionRecord::class );
		$restoredRev->method( 'getMainContentRaw' )->willReturn( $content );

		$this->changeNotifier->expects( $methodsShouldBeCalled ? $this->once() : $this->never() )
			->method( 'notifyOnPageUndeleted' )
			->with( $restoredRev );

		// Only $restoredRev is used by this function, so all other arguments are arbitrary
		$this->handler->onPageUndeleteComplete(
			$this->createMock( ProperPageIdentity::class ),
			$this->mockAuthority,
			'restoring for testOnPageUndeleteComplete',
			$restoredRev,
			$this->createMock( \ManualLogEntry::class ),
			1,
			true,
			[],
		);
	}
}
