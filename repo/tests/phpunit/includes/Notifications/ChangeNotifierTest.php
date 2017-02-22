<?php

namespace Wikibase\Repo\Tests\Notifications;

use Content;
use Revision;
use RuntimeException;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityChange;
use Wikibase\ItemContent;
use Wikibase\Repo\Notifications\ChangeNotifier;
use Wikibase\Repo\Notifications\ChangeTransmitter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Notifications\ChangeNotifier
 *
 * @group Database
 *
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ChangeNotifierTest extends \MediaWikiTestCase {

	private function getChangeNotifier( $expectNotifications = 1 ) {
		$changeTransmitter = $this->getMock( ChangeTransmitter::class );
		$changeTransmitter->expects( $this->exactly( $expectNotifications ) )
			->method( 'transmitChange' );

		$changeFactory = WikibaseRepo::getDefaultInstance()->getEntityChangeFactory();
		return new ChangeNotifier( $changeFactory, [ $changeTransmitter ] );
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
		$title = Title::newFromText( $target->getSerialization() );
		return ItemContent::newFromRedirect( new EntityRedirect( $id, $target ), $title );
	}

	/**
	 * @param Content $content
	 * @param User $user
	 * @param int $revisionId
	 * @param string $timestamp
	 * @param int $parent_id
	 *
	 * @return Revision
	 */
	private function makeRevision( Content $content, User $user, $revisionId, $timestamp, $parent_id = 0 ) {
		return new Revision( [
			'id' => $revisionId,
			'page' => 7,
			'content' => $content,
			'user' => $user->getId(),
			'user_text' => $user->getName(),
			'timestamp' => $timestamp,
			'parent_id' => $parent_id,
		] );
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

		$this->assertChange(
			array(
				'object_id' => strtolower( $content->getEntityId()->getSerialization() ),
				'user_id' => $user->getId(),
				'time' => $timestamp,
				'type' => 'wikibase-item~remove',
				'info' => array(
					'metadata' => array(
						'user_text' => $user->getName(),
						'comment' => 'wikibase-comment-remove',
					)
				)
			),
			$change
		);
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

		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageUndeleted( $revision );
		$fields = $change->getFields();

		$this->assertChange(
			array(
				'object_id' => strtolower( $content->getEntityId()->getSerialization() ),
				'user_id' => $user->getId(),
				'revision_id' => $revisionId,
				'type' => 'wikibase-item~restore',
				'info' => array(
					'metadata' => array(
						'user_text' => $user->getName(),
						'comment' => 'wikibase-comment-restore',
					)
				)
			),
			$change
		);

		$this->assertTrue(
			wfTimestamp() - $fields['time'] < 10,
			"Page undeletions should use the current timestamp, not the one from the revision"
		);
	}

	public function testNotifyOnPageUndeleted_redirect() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$user->setId( 17 );

		$timestamp = '20140523' . '174822';
		$content = $this->makeItemRedirectContent( new ItemId( 'Q12' ), new ItemId( 'Q17' ) );
		$revisionId = 12345;

		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp );

		$notifier = $this->getChangeNotifier( 0 );
		$change = $notifier->notifyOnPageUndeleted( $revision );

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

		$this->assertChange(
			array(
				'object_id' => strtolower( $content->getEntityId()->getSerialization() ),
				'user_id' => $user->getId(),
				'revision_id' => $revisionId,
				'time' => $timestamp,
				'type' => 'wikibase-item~add',
			),
			$change
		);
	}

	public function testNotifyOnPageCreated_redirect() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$content = $this->makeItemRedirectContent( new ItemId( 'Q12' ), new ItemId( 'Q17' ) );
		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp );

		$notifier = $this->getChangeNotifier( 0 );
		$change = $notifier->notifyOnPageCreated( $revision );

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
		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp, $revisionId - 1 );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageModified( $revision, $parent );

		$this->assertChange(
			array(
				'object_id' => strtolower( $content->getEntityId()->getSerialization() ),
				'user_id' => $user->getId(),
				'revision_id' => $revisionId,
				'time' => $timestamp,
				'type' => 'wikibase-item~update',
			),
			$change
		);
	}

	public function testNotifyOnPageModified_redirect() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$itemId = new ItemId( 'Q12' );
		$oldContent = $this->makeItemRedirectContent( $itemId, new ItemId( 'Q17' ) );
		$parent = $this->makeRevision( $oldContent, $user, $revisionId - 1, $timestamp );

		$content = $this->makeItemRedirectContent( $itemId, new ItemId( 'Q19' ) );
		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp, $revisionId - 1 );

		$notifier = $this->getChangeNotifier( 0 );
		$change = $notifier->notifyOnPageModified( $revision, $parent );

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
		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp, $revisionId - 1 );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageModified( $revision, $parent );

		$this->assertChange(
			array(
				'object_id' => strtolower( $content->getEntityId()->getSerialization() ),
				'user_id' => $user->getId(),
				'revision_id' => $revisionId,
				'time' => $timestamp,
				'type' => 'wikibase-item~restore',
			),
			$change
		);
	}

	public function testNotifyOnPageModified_to_redirect() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$itemId = new ItemId( 'Q12' );
		$oldContent = $this->makeItemContent( $itemId );
		$parent = $this->makeRevision( $oldContent, $user, $revisionId - 1, $timestamp );

		$content = $this->makeItemRedirectContent( $itemId, new ItemId( 'Q19' ) );
		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp, $revisionId - 1 );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageModified( $revision, $parent );

		$this->assertChange(
			array(
				'object_id' => strtolower( $content->getEntityId()->getSerialization() ),
				'user_id' => $user->getId(),
				'revision_id' => $revisionId,
				'time' => $timestamp,
				'type' => 'wikibase-item~remove',
			),
			$change
		);
	}

	private function assertChange( $expected, EntityChange $actual ) {
		if ( isset( $expected['type'] ) ) {
			$this->assertSame( $expected['type'], $actual->getType() );
			unset( $expected['type'] );
		}

		if ( isset( $expected['object_id'] ) ) {
			$this->assertSame( $expected['object_id'], $actual->getObjectId() );
			unset( $expected['object_id'] );
		}

		$this->assertFields( $expected, $actual->getFields() );
	}

	private function assertFields( $expected, $actual ) {
		foreach ( $expected as $name => $value ) {
			$this->assertArrayHasKey( $name, $actual );

			if ( is_array( $value ) ) {
				$this->assertFields( $value, $actual[$name] );
			} else {
				$this->assertEquals( $value, $actual[$name] );
			}
		}
	}

}
