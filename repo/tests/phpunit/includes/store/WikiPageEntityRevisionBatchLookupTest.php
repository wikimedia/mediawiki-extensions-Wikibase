<?php

namespace Wikibase\Test;

use MediaWikiTestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Lib\Store\WikiPageEntityRevisionBatchLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Lib\Store\WikiPageEntityRevisionBatchLookup
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseRepo
 * @group medium
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class WikiPageEntityRevisionBatchLookupTest extends MediaWikiTestCase {

	/**
	 * @var EntityRevision[]
	 */
	private static $entityRevisions = array();

	/**
	 * @var EntityRedirect
	 */
	private static $entityRedirect;

	protected function setUp() {
		parent::setUp();

		if ( self::$entityRevisions ) {
			return;
		}

		$item1 = new Item();
		$item1->getFingerprint()->setLabel( 'en', 'Item No 1' );
		self::$entityRevisions[] = $this->storeTestEntity( $item1 );

		$item2 = new Item();
		$item2->getFingerprint()->setLabel( 'en', 'Item No 2' );
		self::$entityRevisions[] = $this->storeTestEntity( $item2 );

		$property = Property::newFromType( 'string' );
		$property->getFingerprint()->setLabel( 'en', 'A property!' );
		self::$entityRevisions[] = $this->storeTestEntity( $property );

		$itemRedirect = new Item();
		$this->storeTestEntity( $itemRedirect );
		$itemRedirectTarget = new Item();
		$itemRedirectTarget->getFingerprint()->setLabel( 'en', 'Redirect Target' );
		$this->storeTestEntity( $itemRedirectTarget );

		self::$entityRedirect = new EntityRedirect( $itemRedirect->getId(), $itemRedirectTarget->getId() );
		$this->storeTestRedirect( self::$entityRedirect );
	}

	private static function storeTestEntity( Entity $entity ) {
		global $wgUser;

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$revision = $store->saveEntity( $entity, "storeTestEntity", $wgUser, EDIT_NEW );

		return $revision;
	}

	private static function storeTestRedirect( EntityRedirect $redirect ) {
		global $wgUser;

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$revision = $store->saveRedirect( $redirect, "storeTestEntity", $wgUser, EDIT_UPDATE );

		return $revision;
	}

	private function getWikiPageEntityRevisionBatchLookup() {
		return new WikiPageEntityRevisionBatchLookup(
			WikibaseRepo::getDefaultInstance()->getEntityContentDataCodec(),
			new BasicEntityIdParser()
		);
	}

	public function testGetEntityRevisions() {
		$item1Id = self::$entityRevisions[0]->getEntity()->getId();
		$item2Id = self::$entityRevisions[1]->getEntity()->getId();
		$propertyId = self::$entityRevisions[2]->getEntity()->getId();
		$redirectId = self::$entityRedirect->getEntityId();
		$nonExistentId = new ItemId( 'Q234234342' );

		$lookup = $this->getWikiPageEntityRevisionBatchLookup();

		// Just one item
		$actual = $lookup->getEntityRevisions( array( $item1Id ) );
		$this->assertEquals( array( $item1Id->getSerialization() => self::$entityRevisions[0] ) , $actual );

		// Just one redirect
		$actual = $lookup->getEntityRevisions( array( $redirectId ) );
		$this->assertEquals( array( $redirectId->getSerialization() => self::$entityRedirect ), $actual );

		// Entity doesn't exit
		$actual = $lookup->getEntityRevisions( array( $nonExistentId ) );
		$this->assertSame( array( $nonExistentId->getSerialization() => null ), $actual );

		// Get various things
		$actual = $lookup->getEntityRevisions( array(
			$item2Id, $propertyId, $nonExistentId, $redirectId
		) );
		$expected =array(
			$item2Id->getSerialization() => self::$entityRevisions[1],
			$propertyId->getSerialization() => self::$entityRevisions[2],
			$nonExistentId->getSerialization() => null,
			$redirectId->getSerialization() => self::$entityRedirect
		);

		$this->assertArrayEquals(
			$expected,
			$actual,
			/* $ordered = */ false,
			/* $named = */ true
		);
	}

}
