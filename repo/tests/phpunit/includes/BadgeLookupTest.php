<?php

namespace Wikibase\Tests\Repo;

use Language;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Repo\BadgeLookup;

/**
 * @covers Wikibase\Repo\BadgeLookup
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRepoTest
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ValueParserFactoryTest extends \MediaWikiTestCase {

	/**
	 * @var array
	 */
	private static $badgeItems = null;

	public function setUp() {
		parent::setUp();

		if ( self::$badgeItems === null ) {
			$this->createItems();
		}
	}

	private function createItems() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		self::$badgeItems = array();

		foreach ( array( 'foo', 'bar', null, 'baz', null ) as $label ) {
			$item = Item::newEmpty();
			if ( $label !== null ) {
				$item->setLabel( 'en', $label );
			}
			$store->saveEntity( $item, 'testing', $GLOBALS['wgUser'], EDIT_NEW );
			$badgeId = $item->getId()->getSerialization();
			self::$badgeItems[$badgeId] = $label === null ? $badgeId : $label;
		}
	}

	public function testGetBadgeTitles() {
		$badgeLookup = new BadgeLookup(
			Language::factory( 'en' ),
			self::$badgeItems,
			WikibaseRepo::getDefaultInstance()->getStore()->getEntityInfoBuilder()
		);

		$this->assertEquals(
			self::$badgeItems,
			$badgeLookup->getBadgeTitles()
		);

		foreach ( self::$badgeItems as $badgeId => $title ) {
			$this->assertEquals( $title, $badgeLookup->getBadgeTitle( new ItemId( $badgeId ) ) );
		}
	}

}
