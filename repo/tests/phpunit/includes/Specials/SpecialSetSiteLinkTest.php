<?php

namespace Wikibase\Repo\Tests\Specials;

use FauxRequest;
use FauxResponse;
use SpecialPageTestBase;
use TestSites;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Specials\SpecialSetSiteLink;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Specials\SpecialSetSiteLink
 * @covers Wikibase\Repo\Specials\SpecialModifyEntity
 * @covers Wikibase\Repo\Specials\SpecialWikibaseRepoPage
 * @covers Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class SpecialSetSiteLinkTest extends SpecialPageTestBase {

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
	 * @var string|null
	 */
	private static $itemId = null;

	/**
	 * @var string|null
	 */
	private static $badgeId = null;

	/**
	 * @var string|null
	 */
	private static $redirectId = null;

	/**
	 * @var string[]
	 */
	private static $oldBadgeItemsSetting;

	protected function newSpecialPage() {
		return new SpecialSetSiteLink();
	}

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( 'wgGroupPermissions', array( '*' => array( 'edit' => true ) ) );

		if ( !self::$badgeId ) {
			$sitesTable = WikibaseRepo::getDefaultInstance()->getSiteStore();
			$sitesTable->clear();
			$sitesTable->saveSites( TestSites::getSites() );

			$this->createItems();
			$this->addBadgeMatcher();
		}

		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		self::$oldBadgeItemsSetting = $settings->getSetting( 'badgeItems' );
		$settings->setSetting( 'badgeItems', array( self::$badgeId => '' ) );
	}

	protected function tearDown() {
		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$settings->setSetting( 'badgeItems', self::$oldBadgeItemsSetting );

		parent::tearDown();
	}

	private function createItems() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$badge = new Item();
		$badge->setLabel( 'de', 'Guter Artikel' );
		$store->saveEntity( $badge, "testing", $GLOBALS['wgUser'], EDIT_NEW );

		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Wikidata', array( $badge->getId() ) );
		$store->saveEntity( $item, "testing", $GLOBALS['wgUser'], EDIT_NEW );

		$redirect = new EntityRedirect( new ItemId( 'Q12345678' ), $item->getId() );
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
			'content' => 'Guter Artikel'
		);
	}

	public function testExecuteEmptyForm() {
		$matchers = self::$matchers;
		// Execute with no subpage value
		list( $output, ) = $this->executeSpecialPage( '', null, 'de' );

		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}'" );
		}
	}

	public function testExecuteOneValuePreset() {
		$matchers = self::$matchers;
		// Execute with one subpage value
		// Note: use language fallback de-ch => de
		list( $output, ) = $this->executeSpecialPage( self::$itemId, null, 'de-ch' );

		$matchers['id']['attributes']['value'] = self::$itemId;

		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag(
				$matcher,
				$output,
				"Failed to match html output with tag '{$key}' passing one subpage value"
			);
		}
	}

	public function testExecuteTwoValuesPreset() {
		$matchers = self::$matchers;
		// Execute with two subpage values
		// Note: use language fallback de-ch => de
		list( $output, ) = $this->executeSpecialPage( self::$itemId . '/dewiki', null, 'de-ch' );

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

		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag(
				$matcher,
				$output,
				"Failed to match html output with tag '{$key}' passing two subpage values"
			);
		}
	}

	public function testExecuteTwoValuesPreset_no_label() {
		$matchers = self::$matchers;
		// Execute with two subpage values
		// Note: language fallback will fail, no label for en
		list( $output, ) = $this->executeSpecialPage( self::$itemId . '/dewiki', null, 'en' );

		// already covered by testExecuteTwoValuesPreset()
		unset( $matchers['id'] );
		unset( $matchers['site'] );
		unset( $matchers['remove'] );

		$matchers['badgelabel']['content'] = self::$badgeId;
		$matchers['value']['attributes']['value'] = 'Wikidata';
		$matchers['badges']['children']['only']['attributes']['selected'] = '';

		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag(
				$matcher,
				$output,
				"Failed to match html output with tag '{$key}' passing two subpage values"
			);
		}
	}

	public function testExecuteRedirect() {
		list( $output, ) = $this->executeSpecialPage( self::$redirectId  . '/dewiki', null, 'qqx' );

		$this->assertRegExp(
			'@<p class="error">\(wikibase-wikibaserepopage-unresolved-redirect: .*?\)</p>@',
			$output,
			'Expected error message'
		);
	}

	public function testExecutePostPreserveSiteLinkWhenNothingEntered() {
		$request = new FauxRequest( array(
			'id' => self::$itemId,
			'site' => 'dewiki',
			'page' => '',
		), true );

		list( $output, ) = $this->executeSpecialPage( '', $request );

		$this->assertTag( array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-setsitelink-page',
				'class' => 'wb-input',
				'name' => 'page',
				'value' => 'Wikidata',
			)
		), $output, 'Value still preserves when no value was entered in the big form' );
	}

	public function testExecutePostModifySiteLink() {
		$lookup = WikibaseRepo::getDefaultInstance()->getEntityLookup();
		$request = new FauxRequest( array(
			'id' => self::$itemId,
			'site' => 'dewiki',
			'page' => 'Wikipedia',
		), true );

		list( , $response ) = $this->executeSpecialPage( '', $request );
		$redirect = $response instanceof FauxResponse ? $response->getHeader( 'Location' ) : null;

		$this->assertContains( self::$itemId, $redirect, "Should redirect to item page" );

		/** @var Item $item */
		$item = $lookup->getEntity( new ItemId( self::$itemId ) );

		$this->assertEquals(
			'Wikipedia',
			$item->getSiteLinkList()->getBySiteId( 'dewiki' )->getPageName(),
			"Should contain new site link"
		);
	}

	public function testExecutePostRemoveSiteLink() {
		$lookup = WikibaseRepo::getDefaultInstance()->getEntityLookup();
		$request = new FauxRequest( array(
			'id' => self::$itemId,
			'site' => 'dewiki',
			'page' => '',
			'remove' => true,
		), true );

		list( , $response ) = $this->executeSpecialPage( '', $request );
		$redirect = $response instanceof FauxResponse ? $response->getHeader( 'Location' ) : null;

		$this->assertContains( self::$itemId, $redirect, "Should redirect to item page" );

		/** @var Item $item */
		$item = $lookup->getEntity( new ItemId( self::$itemId ) );

		$this->assertFalse( $item->hasLinkToSite( 'dewiki' ), "Should no longer contain site link" );
	}

}
