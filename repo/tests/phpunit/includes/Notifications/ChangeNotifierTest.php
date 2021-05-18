<?php

namespace Wikibase\Repo\Tests\Notifications;

use CommentStoreComment;
use Content;
use MediaWiki\Revision\MutableRevisionRecord;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\User\UserIdentityValue;
use MediaWikiIntegrationTestCase;
use RuntimeException;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Tests\Changes\MockRepoClientCentralIdLookup;
use Wikibase\Repo\Content\ItemContent;
use Wikibase\Repo\Notifications\ChangeNotifier;
use Wikibase\Repo\Notifications\ChangeTransmitter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Notifications\ChangeNotifier
 *
 * @group Database
 *
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ChangeNotifierTest extends MediaWikiIntegrationTestCase {

	private function getChangeNotifier( $expectNotifications = 1 ) {
		$changeTransmitter = $this->createMock( ChangeTransmitter::class );
		$changeTransmitter->expects( $this->exactly( $expectNotifications ) )
			->method( 'transmitChange' );

		$changeFactory = WikibaseRepo::getEntityChangeFactory();
		return new ChangeNotifier(
			$changeFactory,
			[ $changeTransmitter ],
			new MockRepoClientCentralIdLookup(
				/** isRepo= */ true
			)
		);
	}

	/**
	 * @param ItemId $id
	 *
	 * @return ItemContent
	 */
	private function makeItemContent( ItemId $id ) {
		return ItemContent::newFromItem( new Item( $id ) );
	}

	/**
	 * @param ItemId $id
	 * @param ItemId $target
	 *
	 * @throws RuntimeException
	 * @return ItemContent
	 */
	protected function makeItemRedirectContent( ItemId $id, ItemId $target ) {
		$title = Title::newFromTextThrow( $target->getSerialization() );
		return ItemContent::newFromRedirect( new EntityRedirect( $id, $target ), $title );
	}

	/**
	 * @param Content $content content for the main slot
	 * @param User $user
	 * @param int $revisionId
	 * @param string $timestamp
	 * @param int $parent_id
	 *
	 * @return RevisionRecord
	 */
	private function makeRevision( Content $content, User $user, $revisionId, $timestamp, $parent_id = 0 ) {
		$revisionRecord = $this->getMockBuilder( RevisionRecord::class )
			->disableOriginalConstructor()
			->getMock();

		$revisionRecord->method( 'getContent' )
			->with( SlotRecord::MAIN )
			->willReturn( $content );

		$revisionRecord->method( 'getUser' )->willReturn( $user );
		$revisionRecord->method( 'getId' )->willReturn( $revisionId );
		$revisionRecord->method( 'getTimestamp' )->willReturn( $timestamp );
		$revisionRecord->method( 'getComment' )->willReturn( null );
		$revisionRecord->method( 'getParentId' )->willReturn( $parent_id );
		$revisionRecord->method( 'getPageId' )->willReturn( 7 );

		return $revisionRecord;
	}

	private function makeUser( $name ) {
		$user = User::newFromName( $name );

		if ( $user->getId() === 0 ) {
			$user->addToDatabase();
		}

		return $user;
	}

	public function testNotifyOnPageDeleted() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$content = $this->makeItemContent( new ItemId( 'Q12' ) );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageDeleted( $content, $user, $timestamp );
		$metadata = $change->getMetadata();

		$this->assertSame( 'Q12', $change->getObjectId() );
		$this->assertSame( $user->getId(), $change->getField( 'user_id' ) );
		$this->assertSame( $timestamp, $change->getTime() );
		$this->assertSame( 'wikibase-item~remove', $change->getType() );
		$this->assertSame( $user->getName(), $metadata['user_text'] );
		$this->assertSame( 'wikibase-comment-remove', $metadata['comment'] );
	}

	public function testNotifyOnPageDeleted_redirect() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$content = $this->makeItemRedirectContent( new ItemId( 'Q12' ), new ItemId( 'Q17' ) );

		$notifier = $this->getChangeNotifier( 0 );
		$change = $notifier->notifyOnPageDeleted( $content, $user, $timestamp );

		$this->assertNull( $change );
	}

	public function testNotifyOnPageUndeleted() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$user->setId( 17 );

		$timestamp = '20140523174822';
		$content = $this->makeItemContent( new ItemId( 'Q12' ) );
		$revisionId = 12345;

		$revisionRecord = $this->makeRevision( $content, $user, $revisionId, $timestamp );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageUndeleted( $revisionRecord );
		$metadata = $change->getMetadata();

		$this->assertSame( 'Q12', $change->getObjectId() );
		$this->assertSame( $user->getId(), $change->getField( 'user_id' ) );
		$this->assertSame( $revisionId, $change->getField( 'revision_id' ) );
		$this->assertSame( 'wikibase-item~restore', $change->getType() );
		$this->assertSame( $user->getName(), $metadata['user_text'] );
		$this->assertSame( 'wikibase-comment-restore', $metadata['comment'] );

		$this->assertTrue(
			$change->getAge() < 10,
			"Page undeletions should use the current timestamp, not the one from the revision"
		);
	}

	public function testNotifyOnPageUndeleted_redirect() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$user->setId( 17 );

		$timestamp = '20140523' . '174822';
		$content = $this->makeItemRedirectContent( new ItemId( 'Q12' ), new ItemId( 'Q17' ) );
		$revisionId = 12345;

		$revisionRecord = $this->makeRevision( $content, $user, $revisionId, $timestamp );

		$notifier = $this->getChangeNotifier( 0 );
		$change = $notifier->notifyOnPageUndeleted( $revisionRecord );

		$this->assertNull( $change );
	}

	public function testNotifyOnPageCreated() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$content = $this->makeItemContent( new ItemId( 'Q12' ) );
		$revisionRecord = $this->makeRevision( $content, $user, $revisionId, $timestamp );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageCreated( $revisionRecord );

		$this->assertSame( 'Q12', $change->getObjectId() );
		$this->assertSame( $user->getId(), $change->getField( 'user_id' ) );
		$this->assertSame( $revisionId, $change->getField( 'revision_id' ) );
		$this->assertSame( $timestamp, $change->getTime() );
		$this->assertSame( 'wikibase-item~add', $change->getType() );
	}

	public function testNotifyOnPageCreated_redirect() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$content = $this->makeItemRedirectContent( new ItemId( 'Q12' ), new ItemId( 'Q17' ) );
		$revisionRecord = $this->makeRevision( $content, $user, $revisionId, $timestamp );

		$notifier = $this->getChangeNotifier( 0 );
		$change = $notifier->notifyOnPageCreated( $revisionRecord );

		$this->assertNull( $change );
	}

	public function testNotifyOnPageModified() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$itemId = new ItemId( 'Q12' );
		$oldContent = $this->makeItemContent( $itemId );
		$parent = $this->makeRevision( $oldContent, $user, $revisionId - 1, $timestamp );

		$content = $this->makeItemContent( $itemId );
		/** @var Item $item */
		$item = $content->getEntity();
		$item->setLabel( 'en', 'Foo' );
		$revisionRecord = $this->makeRevision( $content, $user, $revisionId, $timestamp, $revisionId - 1 );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageModified(
			$revisionRecord,
			$parent
		);

		$this->assertSame( 'Q12', $change->getObjectId() );
		$this->assertSame( $user->getId(), $change->getField( 'user_id' ) );
		$this->assertSame( $revisionId, $change->getField( 'revision_id' ) );
		$this->assertSame( $timestamp, $change->getTime() );
		$this->assertSame( 'wikibase-item~update', $change->getType() );
	}

	public function testNotifyOnPageModified_redirect() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$itemId = new ItemId( 'Q12' );
		$oldContent = $this->makeItemRedirectContent( $itemId, new ItemId( 'Q17' ) );
		$parent = $this->makeRevision( $oldContent, $user, $revisionId - 1, $timestamp );

		$content = $this->makeItemRedirectContent( $itemId, new ItemId( 'Q19' ) );
		$revisionRecord = $this->makeRevision( $content, $user, $revisionId, $timestamp, $revisionId - 1 );

		$notifier = $this->getChangeNotifier( 0 );
		$change = $notifier->notifyOnPageModified(
			$revisionRecord,
			$parent
		);

		$this->assertNull( $change );
	}

	public function testNotifyOnPageModified_from_redirect() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$itemId = new ItemId( 'Q12' );
		$oldContent = $this->makeItemRedirectContent( $itemId, new ItemId( 'Q17' ) );
		$parent = $this->makeRevision( $oldContent, $user, $revisionId - 1, $timestamp );

		$content = $this->makeItemContent( $itemId );
		$revisionRecord = $this->makeRevision( $content, $user, $revisionId, $timestamp, $revisionId - 1 );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageModified(
			$revisionRecord,
			$parent
		);

		$this->assertSame( 'Q12', $change->getObjectId() );
		$this->assertSame( $user->getId(), $change->getField( 'user_id' ) );
		$this->assertSame( $revisionId, $change->getField( 'revision_id' ) );
		$this->assertSame( $timestamp, $change->getTime() );
		$this->assertSame( 'wikibase-item~restore', $change->getType() );
	}

	public function testNotifyOnPageModified_to_redirect() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$itemId = new ItemId( 'Q12' );
		$oldContent = $this->makeItemContent( $itemId );
		$parent = $this->makeRevision( $oldContent, $user, $revisionId - 1, $timestamp );

		$content = $this->makeItemRedirectContent( $itemId, new ItemId( 'Q19' ) );
		$revisionRecord = $this->makeRevision( $content, $user, $revisionId, $timestamp, $revisionId - 1 );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageModified(
			$revisionRecord,
			$parent
		);

		$this->assertSame( 'Q12', $change->getObjectId() );
		$this->assertSame( $user->getId(), $change->getField( 'user_id' ) );
		$this->assertSame( $revisionId, $change->getField( 'revision_id' ) );
		$this->assertSame( $timestamp, $change->getTime() );
		$this->assertSame( 'wikibase-item~remove', $change->getType() );
	}

	public function testNotifyOnPageModified_setsChangeMetadata() {
		$timestamp = '20140523' . '174822';
		$revId = 12345;
		$parentRevId = $revId - 1;

		$itemId = new ItemId( 'Q12' );
		$oldContent = $this->makeItemContent( $itemId );
		$parent = $this->makeRevision( $oldContent, $this->makeUser( 'ChangeNotifierTestUser' ), $parentRevId, $timestamp );

		$content = $this->makeItemContent( $itemId );
		/** @var Item $item */
		$item = $content->getEntity();
		$item->setLabel( 'en', 'Foo' );
		$revRecord = new MutableRevisionRecord( Title::newFromTextThrow( 'Required workaround' ) );
		$revRecord->setComment( CommentStoreComment::newUnsavedComment( 'Test!' ) );
		$revRecord->setTimestamp( $timestamp );
		$revRecord->setId( $revId );
		$revRecord->setParentId( $parentRevId );
		$revRecord->setUser( new UserIdentityValue( 7, 'Mr. Kittens' ) );
		$revRecord->setPageId( 6 );
		$revRecord->setContent( SlotRecord::MAIN, $content );

		$notifier = $this->getChangeNotifier();
		$entityChange = $notifier->notifyOnPageModified(
			$revRecord,
			$parent
		);

		$this->assertSame( $revId, $entityChange->getField( 'revision_id' ), 'revision_id' );
		$this->assertSame( 7, $entityChange->getField( 'user_id' ), 'user_id' );
		$this->assertSame( $itemId->getSerialization(), $entityChange->getObjectId(), 'object_id' );
		$this->assertSame( $timestamp, $entityChange->getTime(), 'timestamp' );
		$this->assertSame( 'Test!', $entityChange->getComment(), 'comment' );

		$metadata = $entityChange->getMetadata();
		$this->assertSame( -7, $metadata['central_user_id'], 'central_user_id' );
		$this->assertSame( $revId, $metadata['rev_id'], 'rev_id' );
		$this->assertSame( $parentRevId, $metadata['parent_id'], 'parent_id' );
		$this->assertSame( 6, $metadata['page_id'], 'page_id' );
		$this->assertSame( 'Mr. Kittens', $metadata['user_text'], 'user_text' );
	}

}
