<?php

namespace Wikibase\Test;

use Wikibase\Repo\Specials\SpecialSetSiteLink;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\ItemContent;
use Wikibase\Settings;

/**
 * @covers Wikibase\Repo\Specials\SpecialSetSiteLink
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialSetSitelinkTest extends SpecialPageTestBase {

	/**
	 * @var array
	 */
	private static $matchers = array();

	/**
	 * @var string
	 */
	private static $itemId;

	/**
	 * @var string
	 */
	private static $badgeId;

	protected function newSpecialPage() {
		return new SpecialSetSiteLink();
	}

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::createItems();

		self::$matchers['id'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-modifyentity-id',
				'class' => 'wb-input',
				'name' => 'id',
			) );
		self::$matchers['site'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-setsitelink-site',
				'class' => 'wb-input',
				'name' => 'site',
			) );
		self::$matchers['page'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-setsitelink-page',
				'class' => 'wb-input',
				'name' => 'page',
			) );

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

		self::$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-setsitelink-submit',
				'class' => 'wb-button',
				'type' => 'submit',
				'name' => 'wikibase-setsitelink-submit',
			) );
	}

	private static function createItems() {
		// create empty badge
		$badge = Item::newEmpty();
		// save badge
		ItemContent::newFromItem( $badge )->save( "testing", null, EDIT_NEW );
		// set the badge id
		self::$badgeId = $badge->getId()->getSerialization();
		// add badge to settings
		Settings::singleton()->setSetting( 'badgeItems', array( self::$badgeId => '' ) );
		// create empty item
		$item = Item::newEmpty();
		// add data and check if it is shown in the form
		$item->addSiteLink( new SiteLink( 'dewiki', 'Wikidata', array( $badge->getId() ) ) );
		// save the item
		ItemContent::newFromItem( $item )->save( "testing", null, EDIT_NEW );
		// set the item id
		self::$itemId = $item->getId()->getSerialization();
	}

	public function testExecute() {
		$matchers = self::$matchers;
		// execute with no subpage value
		list( $output, ) = $this->executeSpecialPage( '' );

		foreach( $matchers as $key => $matcher ){
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

	public function testExecuteOneValue() {
		$matchers = self::$matchers;
		// execute with one subpage value
		list( $output, ) = $this->executeSpecialPage( self::$itemId );
		$matchers['id']['attributes']['value'] = self::$itemId;

		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}' passing one subpage value" );
		}
	}

	public function testExecuteTwoValues() {
		$matchers = self::$matchers;
		// execute with two subpage values
		list( $output, ) = $this->executeSpecialPage( self::$itemId . '/dewiki' );
		$matchers['id']['attributes'] = array(
			'type' => 'hidden',
			'name' => 'id',
			'value' => self::$itemId,
		);
		$matchers['site']['attributes'] = array(
			'type' => 'hidden',
			'name' => 'language',
			'value' => 'dewiki',
		);
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
