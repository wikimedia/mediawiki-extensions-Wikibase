<?php

namespace Wikibase\Test;

use Wikibase\Repo\Specials\SpecialSetSiteLink;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\ItemContent;
use Wikibase\Repo\WikibaseRepo;
use TestSites;

/**
 * @covers Wikibase\Repo\Specials\SpecialSetSiteLink
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class SpecialSetSitelinkTest extends SpecialPageTestBase {

	/**
	 * @var array
	 */
	private static $matchers = array(
		'id' => array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-modifyentity-id',
				'class' => 'wb-input',
				'name' => 'id',
		) ),
		'site' => array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-setsitelink-site',
				'class' => 'wb-input',
				'name' => 'site',
		) ),
		'page' => array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-setsitelink-page',
				'class' => 'wb-input',
				'name' => 'page',
		) ),
		'submit' => array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-setsitelink-submit',
				'class' => 'wb-button',
				'type' => 'submit',
				'name' => 'wikibase-setsitelink-submit',
		) )
	);

	/**
	 * @var string
	 */
	private static $itemId = null;

	/**
	 * @var string
	 */
	private static $badgeId = null;

	/**
	 * @var array
	 */
	private static $oldBadgeItemsSetting;

	protected function newSpecialPage() {
		return new SpecialSetSiteLink();
	}

	public function setUp() {
		parent::setUp();

		if ( !self::$badgeId ) {
			TestSites::insertIntoDb();

			$this->createItems();

			// Experimental setting of badges on the special page
			// @todo remove experimental once JS UI is in place, (also remove the experimental test case)
			if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
				self::$matchers['badges'] = array(
					'tag' => 'input',
					'attributes' => array(
						'id' => 'wb-setsitelink-badges',
						'class' => 'wb-input',
						'name' => 'badges',
					) );
			}
		}

		self::$oldBadgeItemsSetting = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'badgeItems' );
		WikibaseRepo::getDefaultInstance()->getSettings()->setSetting( 'badgeItems', array( self::$badgeId => '' ) );
	}

	public function tearDown() {
		WikibaseRepo::getDefaultInstance()->getSettings()->setSetting( 'badgeItems', self::$oldBadgeItemsSetting );
		parent::tearDown();
	}

	private function createItems() {
		$badge = Item::newEmpty();
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$store->saveEntity( $badge, "testing", $GLOBALS['wgUser'], EDIT_NEW );

		$item = Item::newEmpty();
		$item->addSiteLink( new SiteLink( 'dewiki', 'Wikidata', array( $badge->getId() ) ) );
		$store->saveEntity( $item, "testing", $GLOBALS['wgUser'], EDIT_NEW );

		self::$itemId = $item->getId()->getSerialization();
		self::$badgeId = $badge->getId()->getSerialization();
	}

	public function testExecuteEmptyForm() {
		$matchers = self::$matchers;
		// Execute with no subpage value
		list( $output, ) = $this->executeSpecialPage( '' );

		foreach( $matchers as $key => $matcher ){
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

	public function testExecuteOneValuePreset() {
		$matchers = self::$matchers;
		// Execute with one subpage value
		list( $output, ) = $this->executeSpecialPage( self::$itemId );
		$matchers['id']['attributes']['value'] = self::$itemId;

		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}' passing one subpage value" );
		}
	}

	public function testExecuteTwoValuesPreset() {
		$matchers = self::$matchers;
		// Execute with two subpage values
		list( $output, ) = $this->executeSpecialPage( self::$itemId . '/dewiki' );
		$matchers['id'] = array(
			'tag' => 'input',
			'attributes' => array(
				'type' => 'hidden',
				'name' => 'id',
				'value' => self::$itemId,
		) );

		$matchers['site'] = array(
			'tag' => 'input',
			'attributes' => array(
				'type' => 'hidden',
				'name' => 'site',
				'value' => 'dewiki',
		) );

		$matchers['remove'] = array(
			'tag' => 'input',
			'attributes' => array(
				'type' => 'hidden',
				'name' => 'remove',
				'value' => 'remove',
			) );

		$matchers['value']['attributes']['value'] = 'Wikidata';

		// Experimental setting of badges on the special page
		// @todo remove experimental once JS UI is in place, (also remove the experimental test case)
		if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
			$matchers['badges']['attributes']['value'] = self::$badgeId;
		}

		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}' passing two subpage values" );
		}
	}
}
