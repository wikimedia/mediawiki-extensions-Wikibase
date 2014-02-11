<?php

namespace Wikibase\Test;

use User;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\EntityContentFactory;
use Wikibase\EntityRevisionLookup;
use Wikibase\store\EntityStore;
use Wikibase\store\WikiPageEntityStore;
use Wikibase\WikiPageEntityLookup;

/**
 * @covers Wikibase\store\WikiPageEntityStore
 *
 * @since 0.5
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikiPageEntityStoreTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @see EntityLookupTest::newEntityLoader()
	 *
	 * @return array array( EntityStore, EntityLookup )
	 */
	protected function createStoreAndLookup() {
		//NOTE: we want to test integration of WikiPageEntityLookup and WikiPageEntityStore here!
		$lookup = new WikiPageEntityLookup( false, CACHE_DB );

		$typeMap = array(
			Item::ENTITY_TYPE => CONTENT_MODEL_WIKIBASE_ITEM,
			Property::ENTITY_TYPE => CONTENT_MODEL_WIKIBASE_PROPERTY,
		);

		$store = new WikiPageEntityStore( new EntityContentFactory( $typeMap ) );

		return array( $store, $lookup );
	}

	public function testSaveEntity() {
		/* @var EntityStore $store */
		/* @var EntityRevisionLookup $lookup */
		list( $store, $lookup ) = $this->createStoreAndLookup();
		$user = $GLOBALS['wgUser'];

		// create one
		$one = new Item( array( 'label' => array( 'en' => 'one' ) ) );

		$r1 = $store->saveEntity( $one, 'create one', $user, EDIT_NEW );
		$oneId = $r1->getEntity()->getId();

		$r1actual = $lookup->getEntityRevision( $oneId );
		$this->assertEquals( $r1->getRevision(), $r1actual->getRevision(), 'revid' );
		$this->assertEquals( $r1->getTimestamp(), $r1actual->getTimestamp(), 'timestamp' );
		$this->assertEquals( $r1->getEntity()->getId(), $r1actual->getEntity()->getId(), 'entity id' );

		// update one
		$one = new Item( array( 'entity' => $oneId->getSerialization(), 'label' => array( 'en' => 'ONE' ) ) );

		$r2 = $store->saveEntity( $one, 'update one', $user, EDIT_UPDATE );
		$this->assertNotEquals( $r1->getRevision(), $r2->getRevision(), 'expected new revision id' );

		$r2actual = $lookup->getEntityRevision( $oneId );
		$this->assertEquals( $r2->getRevision(), $r2actual->getRevision(), 'revid' );
		$this->assertEquals( $r2->getTimestamp(), $r2actual->getTimestamp(), 'timestamp' );
		$this->assertEquals( $r2->getEntity()->getId(), $r2actual->getEntity()->getId(), 'entity id' );
	}

	public function provideSaveEntityError() {
		return array(
			'not fresh' => array(
				'entity' => new Item( array( 'label' => array( 'en' => 'one' ) ) ),
				'flags' => EDIT_NEW,
				'baseRevid' => false,
				'error' => 'Wikibase\StorageException'
			),

			'not exists' => array(
				'entity' => new Item( array( 'entity' => 'Q768476834', 'label' => array( 'en' => 'Bwahahaha', 'de' => 'Kähähähä' ) ) ),
				'flags' => EDIT_UPDATE,
				'baseRevid' => false,
				'error' => 'Wikibase\StorageException'
			),

			'bad base' => array(
				'entity' => new Item( array( 'label' => array( 'en' => 'one', 'de' => 'eins' ) ) ),
				'flags' => EDIT_UPDATE,
				'baseRevid' => 1234,
				'error' => 'Wikibase\StorageException'
			),
		);
	}

	/**
	 * @dataProvider provideSaveEntityError
	 */
	public function testSaveEntityError( Entity $entity, $flags, $baseRevId, $error ) {
		/* @var EntityStore $store */
		list( $store, ) = $this->createStoreAndLookup();
		$user = $GLOBALS['wgUser'];

		// setup target item
		$one = new Item( array( 'label' => array( 'en' => 'one' ) ) );
		$r1 = $store->saveEntity( $one, 'create one', $user, EDIT_NEW );

		// inject ids
		if ( is_int( $baseRevId ) ) {
			// use target item's revision as an offset
			$baseRevId += $r1->getRevision();
		}

		if ( $entity->getId() === null ) {
			// use target item's id
			$entity->setId( $r1->getEntity()->getId() );
		}

		// check for error
		$this->setExpectedException( $error );
		$store->saveEntity( $entity, '', $GLOBALS['wgUser'], $flags, $baseRevId );
	}

	public function testUserWasLastToEdit() {
		/* @var WikiPageEntityStore $store */
		/* @var EntityRevisionLookup $lookup */
		list( $store, $lookup ) = $this->createStoreAndLookup();

		$anonUser = User::newFromId(0);
		$anonUser->setName( '127.0.0.1' );
		$user = User::newFromName( "EditEntityTestUser" );
		$item = Item::newEmpty();

		// check for default values, last revision by anon --------------------
		$item->setLabel( 'en', "Test Anon default" );
		$store->saveEntity( $item, 'testing', $anonUser, EDIT_NEW );
		$itemId = $item->getId();

		$res = $store->userWasLastToEdit( $anonUser, $itemId, false );
		$this->assertFalse( $res );

		// check for default values, last revision by sysop --------------------
		$item->setLabel( 'en', "Test SysOp default" );
		$store->saveEntity( $item, 'Test SysOp default', $user, EDIT_UPDATE );
		$res = $store->userWasLastToEdit( $anonUser, $itemId, false );
		$this->assertFalse( $res );

		// check for default values, last revision by anon --------------------
		$item->setLabel( 'en', "Test Anon with user" );
		$store->saveEntity( $item, 'Test Anon with user', $anonUser, EDIT_UPDATE );
		$res = $store->userWasLastToEdit( $anonUser, $itemId, false );
		$this->assertFalse( $res );

		// check for default values, last revision by sysop --------------------
		$item->setLabel( 'en', "Test SysOp with user" );
		$store->saveEntity( $item, 'Test SysOp with user', $user, EDIT_UPDATE );
		$res = $store->userWasLastToEdit( $user, $itemId, false );
		$this->assertFalse( $res );

		// create an edit and check if the anon user is last to edit --------------------
		$lastRevId = $lookup->getLatestRevisionId( $itemId );
		$item->setLabel( 'en', "Test Anon" );
		$store->saveEntity( $item, 'Test Anon', $anonUser, EDIT_UPDATE );
		$res = $store->userWasLastToEdit( $anonUser, $itemId, $lastRevId );
		$this->assertTrue( $res );
		// also check that there is a failure if we use the sysop user
		$res = $store->userWasLastToEdit( $user, $itemId, $lastRevId );
		$this->assertFalse( $res );

		// create an edit and check if the sysop user is last to edit --------------------
		$lastRevId = $lookup->getLatestRevisionId( $itemId );
		$item->setLabel( 'en', "Test SysOp" );
		$store->saveEntity( $item, 'Test SysOp', $user, EDIT_UPDATE );
		$res = $store->userWasLastToEdit( $user, $itemId, $lastRevId );
		$this->assertTrue( $res );

		// also check that there is a failure if we use the anon user
		$res = $store->userWasLastToEdit( $anonUser, $itemId, $lastRevId );
		$this->assertFalse( $res );
	}

	public function testUpdateWatchlist() {
		/* @var WikiPageEntityStore $store */
		list( $store, ) = $this->createStoreAndLookup();

		$user = User::newFromName( "WikiPageEntityStoreTestUser2" );

		if ( $user->getId() === 0 ) {
			$user->addToDatabase();
		}

		$item = Item::newEmpty();
		$store->saveEntity( $item, 'testing', $user, EDIT_NEW );

		$itemId = $item->getId();

		$store->updateWatchlist( $user, $itemId, true );
		$this->assertTrue( $store->isWatching( $user, $itemId ) );

		$store->updateWatchlist( $user, $itemId, false );
		$this->assertFalse( $store->isWatching( $user, $itemId ) );
	}
}
