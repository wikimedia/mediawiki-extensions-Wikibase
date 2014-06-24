<?php

namespace Wikibase\Tests\Repo;

use Content;
use Revision;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityChange;
use Wikibase\EntityContent;
use Wikibase\ItemContent;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Repo\Notifications\NullChangeNotifier;

/**
 * @covers Wikibase\Repo\Notifications\ChangeNotifier
 *
 * @group Database
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ChangeNotifierTest extends \MediaWikiTestCase {

	protected function getChangeNotifier() {
		$notifier = new NullChangeNotifier();

		return $notifier;
	}

	/**
	 * @param ItemId $id
	 *
	 * @return EntityContent
	 */
	protected function makeItemContent( ItemId $id ) {
		$item = Item::newEmpty();
		$item->setId( $id );

		$content = ItemContent::newFromItem( $item );
		return $content;
	}

	/**
	 * @param ItemId $id
	 * @param ItemId $target
	 *
	 * @return EntityContent
	 */
	protected function makeItemRedirectContent( ItemId $id, ItemId $target ) {
		$title = Title::newFromText( $target->getSerialization() );
		$redirect = new EntityRedirect( $id, $target );
		$content = ItemContent::newFromRedirect( $redirect, $title );
		return $content;
	}

	/**
	 * @param Content $content
	 * @param User $user
	 * @param $revisionId
	 * @param $timestamp
	 * @param int $parent_id
	 *
	 * @return Revision
	 */
	protected function makeRevision( Content $content, User $user, $revisionId, $timestamp, $parent_id = 0 ) {
		$revision = new Revision( array(
			'id' => $revisionId,
			'page' => 7,
			'content' => $content,
			'user' => $user->getId(),
			'user_text' => $user->getName(),
			'timestamp' => $timestamp,
			'parent_id' => $parent_id,
		) );

		return $revision;
	}

	protected function makeUser( $name ) {
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

		$this->assertChange( $content->getEntityId(), $user, 0, $timestamp, 'remove', $change );
	}

	public function testNotifyOnPageDeleted_redirect() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$content = $this->makeItemRedirectContent( new ItemId( 'Q12' ), new ItemId( 'Q17' ) );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageDeleted( $content, $user, $timestamp );

		$this->assertNull( $change );
	}

	public function testNotifyOnPageUndeleted() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$user->setId( 17 );

		$timestamp = '20140523' . '174822';
		$content = $this->makeItemContent( new ItemId( 'Q12' ) );
		$revisionId = 12345;

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageUndeleted( $content, $user, $revisionId, $timestamp );

		$this->assertChange( $content->getEntityId(), $user, $revisionId, $timestamp, 'restore', $change );
	}

	public function testNotifyOnPageUndeleted_redirect() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$user->setId( 17 );

		$timestamp = '20140523' . '174822';
		$content = $this->makeItemRedirectContent( new ItemId( 'Q12' ), new ItemId( 'Q17' ) );
		$revisionId = 12345;

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageUndeleted( $content, $user, $revisionId, $timestamp );

		$this->assertNull( $change );
	}

	public function testNotifyOnPageCreated() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$content = $this->makeItemContent( new ItemId( 'Q12' ) );
		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageCreated( $revision );

		$this->assertChange( $content->getEntityId(), $user, $revisionId, $timestamp, 'add', $change );
	}

	public function testNotifyOnPageCreated_redirect() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$content = $this->makeItemRedirectContent( new ItemId( 'Q12' ), new ItemId( 'Q17' ) );
		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageCreated( $revision );

		$this->assertNull( $change );
	}

	public function testNotifyOnPageModified() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$oldContent = $this->makeItemContent( new ItemId( 'Q12' ) );
		$parent = $this->makeRevision( $oldContent, $user, $revisionId-1, $timestamp );

		$content = $this->makeItemContent( $oldContent->getEntityId() );
		$content->getEntity()->setLabel( 'en', 'Foo' );
		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp, $revisionId-1 );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageModified( $revision, $parent );

		$this->assertChange( $content->getEntityId(), $user, $revisionId, $timestamp, 'update', $change );
	}

	public function testNotifyOnPageModified_redirect() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$oldContent = $this->makeItemRedirectContent( new ItemId( 'Q12' ), new ItemId( 'Q17' ) );
		$parent = $this->makeRevision( $oldContent, $user, $revisionId-1, $timestamp );

		$content = $this->makeItemRedirectContent( $oldContent->getEntityId(), new ItemId( 'Q19' ) );
		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp, $revisionId-1 );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageModified( $revision, $parent );

		$this->assertNull( $change );
	}

	public function testNotifyOnPageModified_from_redirect() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$oldContent = $this->makeItemRedirectContent( new ItemId( 'Q12' ), new ItemId( 'Q17' ) );
		$parent = $this->makeRevision( $oldContent, $user, $revisionId-1, $timestamp );

		$content = $this->makeItemContent( $oldContent->getEntityId() );
		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp, $revisionId-1 );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageModified( $revision, $parent );

		$this->assertChange( $content->getEntityId(), $user, $revisionId, $timestamp, 'restore', $change );
	}

	public function testNotifyOnPageModified_to_redirect() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$oldContent = $this->makeItemContent( new ItemId( 'Q12' ) );
		$parent = $this->makeRevision( $oldContent, $user, $revisionId-1, $timestamp );

		$content = $this->makeItemRedirectContent( $oldContent->getEntityId(), new ItemId( 'Q19' ) );
		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp, $revisionId-1 );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageModified( $revision, $parent );

		$this->assertChange( $content->getEntityId(), $user, $revisionId, $timestamp, 'remove', $change );
	}

	protected function assertChange( EntityId $entityId, User $user, $revisionId, $timestamp, $action, EntityChange $change ) {
		$this->assertEquals( 'wikibase-item~' . $action, $change->getType(), 'getType()' );
		$this->assertEquals( $timestamp, $change->getTime(), 'getTime()' );
		$this->assertEquals( $user->getId(), $change->getUser()->getId(), 'getUser()->getId()' );
		$this->assertTrue( $entityId->equals( $change->getEntityId() ), 'getEntityId()' );

		$this->assertEquals( $revisionId, $change->getField( 'revision_id' ), 'getField( "revision_id" )' );
		$this->assertEquals( $user->getId(), $change->getField( 'user_id' ), 'getField( "user_id" )' );
		$this->assertEquals( $entityId->getSerialization(), strtoupper( $change->getField( 'object_id' ) ), 'getField( "object_id" )' );
		$this->assertEquals( $timestamp, $change->getField( 'time' ), 'getField( "time" )' );

		$meta = $change->getMetadata();

		// check meta-data, if it was set.
		if ( $meta ) {
			$this->assertEquals( $user->getName(), $meta['user_text'], 'getMetadata() ["user_text"]' );
			$this->assertEquals( 'wikibase-comment-' . $action, $meta['comment'], 'getMetadata() ["comment"]' );
		}
	}

}
