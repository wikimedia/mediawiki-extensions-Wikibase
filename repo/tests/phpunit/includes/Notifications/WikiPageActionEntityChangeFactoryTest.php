<?php

namespace Wikibase\Repo\Tests\Notifications;

use CommentStoreComment;
use MediaWiki\Revision\MutableRevisionRecord;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\User\UserIdentity;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Tests\Changes\MockRepoClientCentralIdLookup;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\Content\ItemContent;
use Wikibase\Repo\Notifications\WikiPageActionEntityChangeFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Notifications\WikiPageActionEntityChangeFactory
 *
 * @group Database
 *
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 */
class WikiPageActionEntityChangeFactoryTest extends \MediaWikiIntegrationTestCase {

	private const REV_DATA_TIMESTAMP = 'timestamp';
	private const REV_DATA_COMMENT = 'comment';
	private const REV_DATA_ID = 'id';
	private const REV_DATA_CONTENT = 'content';
	private const REV_DATA_PARENT_ID = 'parent_id';
	private const REV_DATA_USER = 'user';
	private const REV_DATA_PAGE_ID = 'page_id';

	public function testNewForPageDeleted() {
		$itemId = new ItemId( 'Q123' );
		$timestamp = '20210519180122';
		$user = $this->getTestUser()->getUser();

		$change = $this->newFactory()->newForPageDeleted(
			$this->newItemContent( $itemId ),
			$user,
			$timestamp
		);

		$this->assertChangeAction( EntityChange::REMOVE, $change );
		$this->assertSame( $itemId, $change->getEntityId() );
		$this->assertHasUserMetaData( $user, $change );
		$this->assertSame( $timestamp, $change->getTime() );
	}

	public function testNewForPageUndeleted() {
		$itemId = new ItemId( 'Q123' );
		$user = $this->getTestUser()->getUser();
		$itemContent = $this->newItemContent( $itemId );
		$revisionData = [
			self::REV_DATA_TIMESTAMP => '20210519180122',
			self::REV_DATA_COMMENT => 'some edit summary',
			self::REV_DATA_ID => 111,
			self::REV_DATA_CONTENT => $itemContent,
			self::REV_DATA_PARENT_ID => 222,
			self::REV_DATA_USER => $user,
			self::REV_DATA_PAGE_ID => 333,
		];

		$change = $this->newFactory()->newForPageUndeleted(
			$itemContent,
			$this->newRevisionRecord( $revisionData )
		);

		$this->assertChangeAction( EntityChange::RESTORE, $change );
		$this->assertSame( $itemId, $change->getEntityId() );
		$this->assertTrue(
			$change->getAge() < 10,
			"Page undeletions should use the current timestamp, not the one from the revision"
		);
		$this->assertHasRevisionMetadata( $revisionData, $change );
	}

	public function testNewForPageCreated() {
		$itemId = new ItemId( 'Q123' );
		$user = $this->getTestUser()->getUser();
		$itemContent = $this->newItemContent( $itemId );
		$timestamp = '20210519180122';
		$revisionData = [
			self::REV_DATA_TIMESTAMP => $timestamp,
			self::REV_DATA_COMMENT => 'some edit summary',
			self::REV_DATA_ID => 111,
			self::REV_DATA_CONTENT => $itemContent,
			self::REV_DATA_PARENT_ID => 222,
			self::REV_DATA_USER => $user,
			self::REV_DATA_PAGE_ID => 333,
		];

		$change = $this->newFactory()->newForPageCreated(
			$itemContent,
			$this->newRevisionRecord( $revisionData )
		);

		$this->assertChangeAction( EntityChange::ADD, $change );
		$this->assertSame( $itemId, $change->getEntityId() );
		$this->assertSame( $timestamp, $change->getTime() );
		$this->assertHasRevisionMetadata( $revisionData, $change );
	}

	public function testNewForPageModified() {
		$itemId = new ItemId( 'Q123' );
		$user = $this->getTestUser()->getUser();
		$itemContent = $this->newItemContent( $itemId );
		$timestamp = '20210519192722';
		$revisionData = [
			self::REV_DATA_TIMESTAMP => $timestamp,
			self::REV_DATA_COMMENT => 'some edit summary',
			self::REV_DATA_ID => 111,
			self::REV_DATA_CONTENT => $itemContent,
			self::REV_DATA_PARENT_ID => 222,
			self::REV_DATA_USER => $user,
			self::REV_DATA_PAGE_ID => 333,
		];

		$change = $this->newFactory()->newForPageModified(
			$this->newRevisionRecord( $revisionData ),
			$this->newItemRedirect()
		);

		$this->assertChangeAction( EntityChange::RESTORE, $change );
		$this->assertSame( $itemId, $change->getEntityId() );
		$this->assertSame( $timestamp, $change->getTime() );
		$this->assertHasRevisionMetadata( $revisionData, $change );
	}

	/**
	 * @dataProvider redirectContentsWithActionProvider
	 */
	public function testNewForPageModified_determinesChangeAction(
		EntityContent $currentRevisionContent,
		EntityContent $parentRevisionContent,
		string $expectedAction
	) {
		$change = $this->newFactory()->newForPageModified(
			$this->newMinimalRevisionRecordWithContent( $currentRevisionContent ),
			$parentRevisionContent
		);

		$this->assertChangeAction( $expectedAction, $change );
	}

	public function redirectContentsWithActionProvider() {
		$itemId = new ItemId( 'Q321' );

		yield 'newer version is a redirect -> deletion' => [
			'currentRevisionContent' => $this->newItemRedirect(),
			'parentRevisionContent' => $this->newItemContent( $itemId ),
			'expectedEntityAction' => EntityChange::REMOVE,
		];

		yield 'older version is a redirect -> restore' => [
			'currentRevisionContent' => $this->newItemContent( $itemId ),
			'parentRevisionContent' => $this->newItemRedirect(),
			'expectedEntityAction' => EntityChange::RESTORE,
		];

		yield 'neither are redirects -> update' => [
			'currentRevisionContent' => $this->newItemContent( $itemId ),
			'parentRevisionContent' => $this->newItemContent( $itemId ),
			'expectedEntityAction' => EntityChange::UPDATE,
		];
	}

	private function assertChangeAction( string $expectedAction, EntityChange $change ) {
		$this->assertStringEndsWith( $expectedAction, $change->getType() );
	}

	private function assertHasUserMetaData( UserIdentity $user, EntityChange $change ) {
		$this->assertSame( $user->getId(), $change->getField( 'user_id' ) );

		$metadata = $change->getMetadata();
		$this->assertSame( $user->getName(), $metadata['user_text'] );
		$this->assertSame(
			-$user->getId(), // stub behavior of MockRepoClientCentralIdLookup
			$metadata['central_user_id']
		);
	}

	private function newFactory(): WikiPageActionEntityChangeFactory {
		return new WikiPageActionEntityChangeFactory(
			WikibaseRepo::getEntityChangeFactory(),
			new MockRepoClientCentralIdLookup( true )
		);
	}

	private function newItemContent( ItemId $id ): ItemContent {
		return ItemContent::newFromItem( new Item( $id ) );
	}

	private function newItemRedirect(): ItemContent {
		$content = $this->createStub( ItemContent::class );
		$content->method( 'isRedirect' )->willReturn( true );
		$content->method( 'copy' )->willReturn( $content );

		return $content;
	}

	private function newRevisionRecord( array $revisionData ): RevisionRecord {
		$revRecord = $this->newMinimalRevisionRecordWithContent( $revisionData[self::REV_DATA_CONTENT] );
		$revRecord->setComment( CommentStoreComment::newUnsavedComment( $revisionData[self::REV_DATA_COMMENT] ) );
		$revRecord->setTimestamp( $revisionData[self::REV_DATA_TIMESTAMP] );
		$revRecord->setId( $revisionData[self::REV_DATA_ID] );
		$revRecord->setParentId( $revisionData[self::REV_DATA_PARENT_ID] );
		$revRecord->setUser( $revisionData[self::REV_DATA_USER] );
		$revRecord->setPageId( $revisionData[self::REV_DATA_PAGE_ID] );

		return $revRecord;
	}

	private function assertHasRevisionMetadata( array $revisionData, EntityChange $change ) {
		$metadata = $change->getMetadata();
		$this->assertSame(
			$revisionData[self::REV_DATA_PAGE_ID],
			$metadata['page_id']
		);
		$this->assertSame(
			$revisionData[self::REV_DATA_PARENT_ID],
			$metadata['parent_id']
		);
		$this->assertSame(
			$revisionData[self::REV_DATA_COMMENT],
			$metadata['comment']
		);
		$this->assertSame(
			$revisionData[self::REV_DATA_ID],
			$metadata['rev_id']
		);

		$this->assertHasUserMetaData( $revisionData[self::REV_DATA_USER], $change );
	}

	private function newMinimalRevisionRecordWithContent( EntityContent $content ): MutableRevisionRecord {
		$revRecord = new MutableRevisionRecord( Title::makeTitle( NS_MAIN, 'Required workaround' ) );
		$revRecord->setContent( SlotRecord::MAIN, $content );
		$revRecord->setUser( $this->getTestUser()->getUserIdentity() );

		return $revRecord;
	}

}
