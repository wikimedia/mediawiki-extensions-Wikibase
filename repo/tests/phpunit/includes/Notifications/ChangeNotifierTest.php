<?php

namespace Wikibase\Tests\Repo;

use Content;
use Revision;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
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

	private function getChangeNotifier() {
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
	private function makeItemContent( ItemId $id ) {
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
	private function makeRevision( Content $content, User $user, $revisionId, $timestamp, $parent_id = 0 ) {
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

		$this->assertFields(
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
			$change->getFields()
		);
	}

	public function testNotifyOnPageUndeleted() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$user->setId( 17 );

		$timestamp = '20140523' . '174822';
		$content = $this->makeItemContent( new ItemId( 'Q12' ) );
		$revisionId = 12345;

		$revision = new Revision( array(
			'id' => $revisionId,
			'content' => $content,
			'timestamp' => $timestamp,
			'user' => $user->getId(),
		) );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageUndeleted( $revision );

		$this->assertFields(
			array(
				'object_id' => strtolower( $content->getEntityId()->getSerialization() ),
				'user_id' => $user->getId(),
				'revision_id' => $revisionId,
				'time' => $timestamp,
				'type' => 'wikibase-item~restore',
				'info' => array(
					'metadata' => array(
						'user_text' => $user->getName(),
						'comment' => 'wikibase-comment-restore',
					)
				)
			),
			$change->getFields()
		);
	}

	public function testNotifyOnPageCreated() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$content = $this->makeItemContent( new ItemId( 'Q12' ) );
		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp );

		$notifier = $this->getChangeNotifier();
		$change = $notifier->notifyOnPageCreated( $revision );

		$this->assertFields(
			array(
				'object_id' => strtolower( $content->getEntityId()->getSerialization() ),
				'user_id' => $user->getId(),
				'revision_id' => $revisionId,
				'time' => $timestamp,
				'type' => 'wikibase-item~add',
			),
			$change->getFields()
		);
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

		$this->assertFields(
			array(
				'object_id' => strtolower( $content->getEntityId()->getSerialization() ),
				'user_id' => $user->getId(),
				'revision_id' => $revisionId,
				'time' => $timestamp,
				'type' => 'wikibase-item~update',
			),
			$change->getFields()
		);
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
