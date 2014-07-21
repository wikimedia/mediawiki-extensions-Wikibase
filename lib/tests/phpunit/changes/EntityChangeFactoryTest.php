<?php

namespace Wikibase\Lib\Test\Change;

use Content;
use ContentHandler;
use Revision;
use RuntimeException;
use Title;
use User;
use Wikibase\ChangesTable;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\EntityChange;
use Wikibase\EntityContent;
use Wikibase\EntityFactory;
use Wikibase\ItemContent;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Store\EntityRedirect;

/**
 * @covers Wikibase\Lib\EntityChangeFactory
 *
 * @since 0.5
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityChangeFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return EntityChangeFactory
	 */
	public function getEntityChangeFactory() {
		// NOTE: always use a local changes table for testing!
		$changesDatabase = false;

		$entityClasses = array(
			Item::ENTITY_TYPE => 'Wikibase\DataModel\Entity\Item',
			Property::ENTITY_TYPE => 'Wikibase\DataModel\Entity\Property',
		);

		$changeClasses = array(
			Item::ENTITY_TYPE => 'Wikibase\ItemChange',
		);

		$factory = new EntityChangeFactory(
			new ChangesTable( $changesDatabase ),
			new EntityFactory( $entityClasses ),
			$changeClasses
		);

		return $factory;
	}

	public function newForEntityProvider() {
		return array(
			'add item' => array( EntityChange::ADD, new ItemId( 'Q17' ), 'Wikibase\ItemChange' ),
			'remove property' => array( EntityChange::REMOVE, new PropertyId( 'P17' ), 'Wikibase\EntityChange' ),
		);
	}

	/**
	 * @dataProvider newForEntityProvider
	 *
	 * @param string $action
	 * @param EntityId $entityId
	 * @param string $expectedClass
	 */
	public function testNewForEntity( $action, $entityId, $expectedClass ) {
		$factory = $this->getEntityChangeFactory();

		$change = $factory->newForEntity( $action, $entityId );
		$this->assertInstanceOf( $expectedClass, $change );
		$this->assertEquals( $action, $change->getAction() );
		$this->assertEquals( $entityId, $change->getEntityId() );
	}

	public function newFromUpdateProvider() {
		$item1 = Item::newEmpty();
		$item1->setId( new ItemId( 'Q1' ) );

		$item2 = Item::newEmpty();
		$item2->setId( new ItemId( 'Q2' ) );

		$prop1 = Property::newFromType( 'string' );
		$prop1->setId( new PropertyId( 'P1' ) );

		return array(
			'add item' => array( EntityChange::ADD, null, $item1, 'wikibase-item~add' ),
			'update item' => array( EntityChange::UPDATE, $item1, $item2, 'wikibase-item~update' ),
			'remove property' => array( EntityChange::REMOVE, $prop1, null, 'wikibase-property~remove' ),
		);
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

	private function itemSupportsRedirects() {
		$handler = ContentHandler::getForModelID( 'wikibase-item' );
		return $handler->supportsRedirects();
	}

	/**
	 * @param ItemId $id
	 * @param ItemId $target
	 *
	 * @throws RuntimeException
	 * @return EntityContent
	 */
	private function makeItemRedirectContent( ItemId $id, ItemId $target ) {
		if ( !$this->itemSupportsRedirects() ) {
			throw new RuntimeException( 'Redirects are not yet supported.' );
		}

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

	/**
	 * @dataProvider newFromUpdateProvider
	 *
	 * @param string $action
	 * @param Entity $oldEntity
	 * @param Entity $newEntity
	 * @param string $expectedType
	 */
	public function testNewFromUpdate( $action, $oldEntity, $newEntity, $expectedType ) {
		$factory = $this->getEntityChangeFactory();

		$entityId = ( $newEntity === null ) ? $oldEntity->getId() : $newEntity->getId();

		$change = $factory->newFromUpdate( $action, $oldEntity, $newEntity );

		$this->assertEquals( $action, $change->getAction() );
		$this->assertEquals( $entityId, $change->getEntityId() );
		$this->assertEquals( $expectedType, $change->getType() );
	}

	public function testGetOnPageDeletedChange() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$content = $this->makeItemContent( new ItemId( 'Q12' ) );

		$factory = $this->getEntityChangeFactory();
		$change = $factory->getOnPageDeletedChange( $content, $user, $timestamp );

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

	public function testGetOnPageDeletedChange_redirect() {
		if ( !$this->itemSupportsRedirects() ) {
			// As of 2014-06-30, redirects are still experimental.
			// So do a feature check before trying to test redirects.
			$this->markTestSkipped( 'Redirects not yet supported.' );
		}

		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$content = $this->makeItemRedirectContent( new ItemId( 'Q12' ), new ItemId( 'Q17' ) );

		$factory = $this->getEntityChangeFactory();
		$change = $factory->getOnPageDeletedChange( $content, $user, $timestamp );

		$this->assertNull( $change );
	}

	public function testGetOnPageUndeletedChange() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$user->setId( 17 );

		$timestamp = '20140523' . '174822';
		$content = $this->makeItemContent( new ItemId( 'Q12' ) );
		$revisionId = 12345;

		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp );

		$factory = $this->getEntityChangeFactory();
		$change = $factory->getOnPageUndeletedChange( $revision );

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

	public function testGetOnPageUndeletedChange_redirect() {
		if ( !$this->itemSupportsRedirects() ) {
			// As of 2014-06-30, redirects are still experimental.
			// So do a feature check before trying to test redirects.
			$this->markTestSkipped( 'Redirects not yet supported.' );
		}

		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$user->setId( 17 );

		$timestamp = '20140523' . '174822';
		$content = $this->makeItemRedirectContent( new ItemId( 'Q12' ), new ItemId( 'Q17' ) );
		$revisionId = 12345;

		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp );

		$factory = $this->getEntityChangeFactory();
		$change = $factory->getOnPageUndeletedChange( $revision );

		$this->assertNull( $change );
	}

	public function testGetOnPageCreatedChange() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$content = $this->makeItemContent( new ItemId( 'Q12' ) );
		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp );

		$factory = $this->getEntityChangeFactory();
		$change = $factory->getOnPageCreatedChange( $revision );

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

	public function testGetOnPageCreatedChange_redirect() {
		if ( !$this->itemSupportsRedirects() ) {
			// As of 2014-06-30, redirects are still experimental.
			// So do a feature check before trying to test redirects.
			$this->markTestSkipped( 'Redirects not yet supported.' );
		}

		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$content = $this->makeItemRedirectContent( new ItemId( 'Q12' ), new ItemId( 'Q17' ) );
		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp );

		$factory = $this->getEntityChangeFactory();
		$change = $factory->getOnPageCreatedChange( $revision );

		$this->assertNull( $change );
	}

	public function testGetOnPageModifiedChange() {
		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$oldContent = $this->makeItemContent( new ItemId( 'Q12' ) );
		$parent = $this->makeRevision( $oldContent, $user, $revisionId-1, $timestamp );

		$content = $this->makeItemContent( $oldContent->getEntityId() );
		$content->getEntity()->setLabel( 'en', 'Foo' );
		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp, $revisionId-1 );

		$factory = $this->getEntityChangeFactory();
		$change = $factory->getOnPageModifiedChange( $revision, $parent );

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

	public function testGetOnPageModifiedChange_redirect() {
		if ( !$this->itemSupportsRedirects() ) {
			// As of 2014-06-30, redirects are still experimental.
			// So do a feature check before trying to test redirects.
			$this->markTestSkipped( 'Redirects not yet supported.' );
		}

		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$oldContent = $this->makeItemRedirectContent( new ItemId( 'Q12' ), new ItemId( 'Q17' ) );
		$parent = $this->makeRevision( $oldContent, $user, $revisionId-1, $timestamp );

		$content = $this->makeItemRedirectContent( $oldContent->getEntityId(), new ItemId( 'Q19' ) );
		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp, $revisionId-1 );

		$factory = $this->getEntityChangeFactory();
		$change = $factory->getOnPageModifiedChange( $revision, $parent );

		$this->assertNull( $change );
	}

	public function testGetOnPageModifiedChange_from_redirect() {
		if ( !$this->itemSupportsRedirects() ) {
			// As of 2014-06-30, redirects are still experimental.
			// So do a feature check before trying to test redirects.
			$this->markTestSkipped( 'Redirects not yet supported.' );
		}

		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$oldContent = $this->makeItemRedirectContent( new ItemId( 'Q12' ), new ItemId( 'Q17' ) );
		$parent = $this->makeRevision( $oldContent, $user, $revisionId-1, $timestamp );

		$content = $this->makeItemContent( $oldContent->getEntityId() );
		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp, $revisionId-1 );

		$factory = $this->getEntityChangeFactory();
		$change = $factory->getOnPageModifiedChange( $revision, $parent );

		$this->assertFields(
			array(
				'object_id' => strtolower( $content->getEntityId()->getSerialization() ),
				'user_id' => $user->getId(),
				'revision_id' => $revisionId,
				'time' => $timestamp,
				'type' => 'wikibase-item~restore',
			),
			$change->getFields()
		);
	}

	public function testGetOnPageModifiedChange_to_redirect() {
		if ( !$this->itemSupportsRedirects() ) {
			// As of 2014-06-30, redirects are still experimental.
			// So do a feature check before trying to test redirects.
			$this->markTestSkipped( 'Redirects not yet supported.' );
		}

		$user = $this->makeUser( 'ChangeNotifierTestUser' );
		$timestamp = '20140523' . '174822';
		$revisionId = 12345;

		$oldContent = $this->makeItemContent( new ItemId( 'Q12' ) );
		$parent = $this->makeRevision( $oldContent, $user, $revisionId-1, $timestamp );

		$content = $this->makeItemRedirectContent( $oldContent->getEntityId(), new ItemId( 'Q19' ) );
		$revision = $this->makeRevision( $content, $user, $revisionId, $timestamp, $revisionId-1 );

		$factory = $this->getEntityChangeFactory();
		$change = $factory->getOnPageModifiedChange( $revision, $parent );

		$this->assertFields(
			array(
				'object_id' => strtolower( $content->getEntityId()->getSerialization() ),
				'user_id' => $user->getId(),
				'revision_id' => $revisionId,
				'time' => $timestamp,
				'type' => 'wikibase-item~remove',
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
