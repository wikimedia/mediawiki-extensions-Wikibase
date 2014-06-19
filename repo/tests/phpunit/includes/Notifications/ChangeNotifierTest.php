<?php

namespace Wikibase\Tests\Repo;

use Content;
use Revision;
use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityChange;
use Wikibase\EntityContent;
use Wikibase\ItemContent;
use Wikibase\Repo\Notifications\ChangeNotifier;
use Wikibase\Repo\Notifications\DummyChangeTransmitter;
use Wikibase\Repo\WikibaseRepo;

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
		$notifier = new ChangeNotifier(
			WikibaseRepo::getDefaultInstance()->getEntityChangeFactory(),
			new DummyChangeTransmitter()
		);

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
