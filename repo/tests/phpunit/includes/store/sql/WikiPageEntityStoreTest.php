<?php

namespace Wikibase\Test;

use Wikibase\Entity;
use Wikibase\EntityContentFactory;
use Wikibase\EntityRevisionLookup;
use Wikibase\Item;
use Wikibase\Property;
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
}
