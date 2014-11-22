<?php

namespace Wikibase\Test;

use Language;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Hooks\LinkBeginHookHandler;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Hooks\LinkBeginHookHandler
 *
 * @since 0.5
 *
 * @group WikibaseRepo
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LinkBeginHookHandlerTest extends \MediaWikiTestCase {

	/**
	 * @var ItemId
	 */
	private static $itemId = null;

	/**
	 * @var ItemId
	 */
	private static $noLabelItemId = null;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup = null;

	protected function setUp() {
		parent::setUp();

		$language = Language::factory( 'en' );

		$this->setMwGlobals( array(
			'wgLanguageCode' =>  'en',
			'wgLang' => $language,
			'wgContLang' => $language
		) );

		if ( self::$itemId === null ) {
			$this->setupItems();
		}
	}

	private function setupItems() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$item = Item::newEmpty();
		$item->setLabel( 'en', 'linkbegin-label' );
		$item->setDescription( 'en', 'linkbegin-description' );

		$user = $GLOBALS['wgUser'];

		$entityRevision = $store->saveEntity( $item, 'testing', $user, EDIT_NEW );
		self::$itemId = $entityRevision->getEntity()->getId();

		$entityRevision = $store->saveEntity( Item::newEmpty(), 'testing', $user, EDIT_NEW );
		self::$noLabelItemId = $entityRevision->getEntity()->getId();
	}

	public function testDoOnLinkBegin() {
		$contextTitle = Title::newFromText( 'Special:Recentchanges' );
		$linkBeginHookHandler = $this->getLinkBeginHookHandler( $contextTitle );

		$title = $this->getEntityTitleLookup()->getTitleForId( self::$itemId );

		$html = $title->getFullText();
		$customAttribs = array();

		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs );

		$expectedHtml = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr">linkbegin-label</span> '
			. '<span class="wb-itemlink-id">(' . self::$itemId->getSerialization()
			. ')</span></span>';

		$this->assertEquals( $expectedHtml, $html );

		$this->assertContains( 'linkbegin-label', $customAttribs['title'] );
		$this->assertContains( 'linkbegin-description', $customAttribs['title'] );
	}

	public function testDoOnLinkBegin_onNonSpecialPage() {
		$linkBeginHookHandler = $this->getLinkBeginHookHandler( Title::newMainPage() );

		$title = $this->getEntityTitleLookup()->getTitleForId( self::$itemId );

		$titleText = $title->getFullText();
		$html = $titleText;
		$customAttribs = array();

		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs );

		$this->assertEquals( $titleText, $html );
		$this->assertEquals( array(), $customAttribs );
	}

	public function testDoOnLinkBegin_nonEntityTitleLink() {
		$contextTitle = Title::newFromText( 'Special:Recentchanges' );
		$linkBeginHookHandler = $this->getLinkBeginHookHandler( $contextTitle );

		$title = Title::newMainPage();

		$titleText = $title->getFullText();
		$html = $titleText;
		$customAttribs = array();

		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs );

		$this->assertEquals( $titleText, $html );
		$this->assertEquals( array(), $customAttribs );
	}

	public function testDoOnLinkBegin_unknownEntityTitle() {
		$contextTitle = Title::newFromText( 'Special:Recentchanges' );
		$linkBeginHookHandler = $this->getLinkBeginHookHandler( $contextTitle );

		$itemId = ItemId::newFromNumber( mt_rand( 0, 9999999999 ) );
		$title = $this->getEntityTitleLookup()->getTitleForId( $itemId );

		$titleText = $title->getFullText();
		$html = $titleText;
		$customAttribs = array();

		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs );

		$this->assertEquals( $titleText, $html );
		$this->assertEquals( array(), $customAttribs );
	}

	public function testDoOnLinkBegin_itemHasNoLabel() {
		$contextTitle = Title::newFromText( 'Special:Recentchanges' );
		$linkBeginHookHandler = $this->getLinkBeginHookHandler( $contextTitle );

		$title = $this->getEntityTitleLookup()->getTitleForId( self::$noLabelItemId );

		$html = $title->getFullText();
		$customAttribs = array();

		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs );

		$expected = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr"></span> '
			. '<span class="wb-itemlink-id">('
			. self::$noLabelItemId->getSerialization()
			. ')</span></span>';

		$this->assertEquals( $expected, $html );
		$this->assertContains( self::$noLabelItemId->getSerialization(), $customAttribs['title'] );
	}

	private function getEntityTitleLookup() {
		if ( $this->entityTitleLookup === null ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$this->entityTitleLookup = $wikibaseRepo->getEntityTitleLookup();
		}

		return $this->entityTitleLookup;
	}

	private function getLinkBeginHookHandler( Title $title ) {
		$context = RequestContext::getMain();
		$context->setTitle( $title );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		return new LinkBeginHookHandler(
			$this->getEntityTitleLookup(),
			$wikibaseRepo->getLanguageFallbackChainFactory(),
			$context
		);

	}

}
