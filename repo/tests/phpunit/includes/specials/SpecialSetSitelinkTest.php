<?php

namespace Wikibase\Test;

use Wikibase\Repo\Specials\SpecialSetSiteLink;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\ItemContent;
use Wikibase\Settings;
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

	private static $oldBadgeItems = null;

	protected function newSpecialPage() {
		return new SpecialSetSiteLink();
	}

	public function setUp() {
		parent::setUp();

		if ( !self::$badgeId ) {
			TestSites::insertIntoDb();

			self::$badgeId = $this->getBadgeItemId();

			self::$itemId = $this->getTestItemId( self::$badgeId )->getSerialization();

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

		self::$oldBadgeItems = Settings::singleton()->getSetting( 'badgeItems' );
		Settings::singleton()->setSetting( 'badgeItems', array( self::$badgeId ) );
	}

	public function tearDown() {
		Settings::singleton()->setSetting( 'badgeItems', self::$oldBadgeItems );
		parent::tearDown();
	}

	/**
	 * @return ItemId
	 */
	protected function getBadgeItemId() {
		$badge = Item::newEmpty();
		ItemContent::newFromItem( $badge )->save( "testing", null, EDIT_NEW );

		return $badge->getId();
	}

	/**
	 * @param ItemId $badgeId
	 * @return ItemId
	 */
	private function getTestItemId( $badgeId ) {
		$item = Item::newEmpty();
		$item->addSiteLink( new SiteLink( 'dewiki', 'Wikidata', array( $badgeId ) ) );
		ItemContent::newFromItem( $item )->save( "testing", null, EDIT_NEW );

		return $item->getId();
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
