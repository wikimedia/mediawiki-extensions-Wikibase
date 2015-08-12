<?php

namespace Wikibase\Test;

use MediaWikiTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Repo\Store\SQL\WikiPageEntityRedirectLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Store\SQL\WikiPageEntityRedirectLookup
 *
 * @group Medium
 * @group Database
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GNU GPL v2+
 * @author Marius Hoch
 */
class WikiPageEntityRedirectLookupTest extends MediaWikiTestCase {

	/**
	 * @var ItemId|null
	 */
	private $itemId = null;

	/**
	 * @var ItemId[]
	 */
	private $redirectItemIds = array();

	public function setUp() {
		parent::setUp();

		if ( $this->itemId === null ) {
			$this->setUpEntities();
		}
	}

	private function setUpEntities() {
		global $wgUser;

		$entityStore = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$item = new Item();
		$entityStore->saveEntity( $item, "WikiPageEntityRedirectLookupTest", $wgUser, EDIT_NEW );

		$redirectItem1 = new Item();
		$entityStore->assignFreshId( $redirectItem1 );
		$redirect = new EntityRedirect( $redirectItem1->getId(), $item->getId() );
		$entityStore->saveRedirect( $redirect, "WikiPageEntityRedirectLookupTest", $wgUser, EDIT_NEW );

		$redirectItem2 = new Item();
		$entityStore->assignFreshId( $redirectItem2 );
		$redirect = new EntityRedirect( $redirectItem2->getId(), $item->getId() );
		$entityStore->saveRedirect( $redirect, "WikiPageEntityRedirectLookupTest", $wgUser, EDIT_NEW );

		$this->itemId = $item->getId();
		$this->redirectItemIds = array( $redirectItem1->getId(), $redirectItem2->getId() );
	}

	public function testGetRedirectForEntityId() {
		$res = $this->getWikiPageEntityRedirectLookup()->getRedirectForEntityId( $this->redirectItemIds[0] );

		$this->assertEquals( $this->itemId, $res );
	}

	public function testGetRedirectForEntityId_entityDoesNotExist() {
		$res = $this->getWikiPageEntityRedirectLookup()->getRedirectForEntityId( new ItemId( 'Q48758903' ) );

		$this->assertFalse( $res );
	}

	public function testGetRedirectForEntityId_entityNotARedirect() {
		$res = $this->getWikiPageEntityRedirectLookup()->getRedirectForEntityId( $this->itemId );

		$this->assertNull( $res );
	}

	public function testGetRedirectIds() {
		$res = $this->getWikiPageEntityRedirectLookup()->getRedirectIds( $this->itemId );

		$this->assertEquals( $this->redirectItemIds, $res );
	}

	public function testGetRedirectIds_entityDoesNotExist() {
		$res = $this->getWikiPageEntityRedirectLookup()->getRedirectIds( new ItemId( 'Q48758903' ) );

		$this->assertSame( array(), $res );
	}

	private function getWikiPageEntityRedirectLookup() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		return new WikiPageEntityRedirectLookup(
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getEntityIdLookup()
		);
	}

}
