<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Repo\Specials\SpecialSetSiteLink;
use Wikibase\Repo\WikibaseRepo;

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
	 * @var string
	 */
	private static $redirectId = null;

	/**
	 * @var array
	 */
	private static $oldBadgeItemsSetting;

	protected function newSpecialPage() {
		return new SpecialSetSiteLink();
	}

	protected function setUp() {
		parent::setUp();

		if ( !self::$badgeId ) {
			$sitesTable = WikibaseRepo::getDefaultInstance()->getSiteStore();
			$sitesTable->clear();
			$sitesTable->saveSites( \TestSites::getSites() );

			$this->createItems();
			$this->addBadgeMatcher();
		}

		self::$oldBadgeItemsSetting = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'badgeItems' );
		WikibaseRepo::getDefaultInstance()->getSettings()->setSetting( 'badgeItems', array( self::$badgeId => '' ) );
	}

	protected function tearDown() {
		WikibaseRepo::getDefaultInstance()->getSettings()->setSetting( 'badgeItems', self::$oldBadgeItemsSetting );
		parent::tearDown();
	}

	private function createItems() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$badge = Item::newEmpty();
		$badge->setLabel( 'en', 'Good article' );
		$store->saveEntity( $badge, "testing", $GLOBALS['wgUser'], EDIT_NEW );

		$item = Item::newEmpty();
		$item->addSiteLink( new SiteLink( 'dewiki', 'Wikidata', array( $badge->getId() ) ) );
		$store->saveEntity( $item, "testing", $GLOBALS['wgUser'], EDIT_NEW );

		$redirect = new EntityRedirect( new ItemId('Q12345678'), $item->getId() );
		$store->saveRedirect( $redirect, "testing", $GLOBALS['wgUser'], EDIT_NEW );

		self::$badgeId = $badge->getId()->getSerialization();
		self::$itemId = $item->getId()->getSerialization();
		self::$redirectId = $redirect->getEntityId()->getSerialization();
	}

	private function addBadgeMatcher() {
		$name = 'badge-' . self::$badgeId;
		self::$matchers['badgeinput'] = array(
			'tag' => 'input',
			'attributes' => array(
				'name' => $name,
				'id' => $name,
				'type' => 'checkbox'
			) );

		self::$matchers['badgelabel'] = array(
			'tag' => 'label',
			'attributes' => array(
				'for' => $name
			),
			'content' => 'Good article'
		);
	}

	public function testExecuteEmptyForm() {
		$matchers = self::$matchers;
		// Execute with no subpage value
		list( $output, ) = $this->executeSpecialPage( '', null, 'en' );

		foreach( $matchers as $key => $matcher ){
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}'" );
		}
	}

	public function testExecuteOneValuePreset() {
		$matchers = self::$matchers;
		// Execute with one subpage value
		list( $output, ) = $this->executeSpecialPage( self::$itemId, null, 'en' );

		$matchers['id']['attributes']['value'] = self::$itemId;

		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}' passing one subpage value" );
		}
	}

	public function testExecuteTwoValuesPreset() {
		$matchers = self::$matchers;
		// Execute with two subpage values
		list( $output, ) = $this->executeSpecialPage( self::$itemId . '/dewiki', null, 'en' );

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

		$matchers['badges']['children']['only']['attributes']['selected'] = '';

		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}' passing two subpage values" );
		}
	}

	public function testExecuteRedirect() {
		list( $output, ) = $this->executeSpecialPage( self::$redirectId  . '/dewiki', null, 'qqx' );

		$this->assertRegExp( '@<p class="error">\(wikibase-wikibaserepopage-unresolved-redirect: .*?\)</p>@', $output, "Expected error message" );
	}

}
