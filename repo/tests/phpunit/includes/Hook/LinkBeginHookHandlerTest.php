<?php

namespace Wikibase\Test;

use Language;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\TermLookup;
use Wikibase\Repo\Hook\LinkBeginHookHandler;
use Wikibase\Repo\Store\PageEntityIdLookup;

/**
 * @covers Wikibase\Repo\Hook\LinkBeginHookHandler
 *
 * @since 0.5
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class LinkBeginHookHandlerTest extends \MediaWikiTestCase {

	const ITEM_WITH_LABEL = 'Q1';
	const ITEM_WITHOUT_LABEL = 'Q11';
	const ITEM_DELETED = 'Q111';

	public function testDoOnLinkBegin() {
		$contextTitle = Title::newFromText( 'Special:Recentchanges' );
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = Title::makeTitle( NS_MAIN, self::ITEM_WITH_LABEL );
		$title->resetArticleID( 1 );
		$this->assertTrue( $title->exists() ); // sanity check

		$html = $title->getFullText();
		$customAttribs = array();

		$out = $this->getOutputPage( $contextTitle );
		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $out );

		$expectedHtml = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr">linkbegin-label</span> '
			. '<span class="wb-itemlink-id">(' . self::ITEM_WITH_LABEL . ')</span></span>';

		$this->assertEquals( $expectedHtml, $html );

		$this->assertContains( 'linkbegin-label', $customAttribs['title'] );
		$this->assertContains( 'linkbegin-description', $customAttribs['title'] );

		$this->assertContains( 'wikibase.common', $out->getModuleStyles() );
	}

	public function testDoOnLinkBegin_onNonSpecialPage() {
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = Title::makeTitle( NS_MAIN, self::ITEM_WITH_LABEL );
		$title->resetArticleID( 1 );
		$this->assertTrue( $title->exists() ); // sanity check

		$titleText = $title->getFullText();
		$html = $titleText;
		$customAttribs = array();

		$out = $this->getOutputPage( Title::newMainPage() );
		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $out );

		$this->assertEquals( $titleText, $html );
		$this->assertEquals( array(), $customAttribs );
	}

	public function testDoOnLinkBegin_nonEntityTitleLink() {
		$contextTitle = Title::newFromText( 'Special:Recentchanges' );
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = Title::newMainPage();
		$title->resetArticleID( 1 );
		$this->assertTrue( $title->exists() ); // sanity check

		$titleText = $title->getFullText();
		$html = $titleText;
		$customAttribs = array();

		$out = $this->getOutputPage( $contextTitle );
		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $out );

		$this->assertEquals( $titleText, $html );
		$this->assertEquals( array(), $customAttribs );
	}

	public function testDoOnLinkBegin_unknownEntityTitle() {
		$contextTitle = Title::newFromText( 'Special:Recentchanges' );
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = Title::makeTitle( NS_MAIN, self::ITEM_DELETED );
		$title->resetArticleID( 0 );
		$this->assertFalse( $title->exists() ); // sanity check

		$titleText = $title->getFullText();
		$html = $titleText;
		$customAttribs = array();

		$out = $this->getOutputPage( $contextTitle );
		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $out );

		$this->assertEquals( $titleText, $html );
		$this->assertEquals( array(), $customAttribs );
	}

	public function testDoOnLinkBegin_itemHasNoLabel() {
		$contextTitle = Title::newFromText( 'Special:Recentchanges' );
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = Title::makeTitle( NS_MAIN, self::ITEM_WITHOUT_LABEL );
		$title->resetArticleID( 1 );
		$this->assertTrue( $title->exists() ); // sanity check

		$html = $title->getFullText();
		$customAttribs = array();

		$out = $this->getOutputPage( $contextTitle );
		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $out );

		$expected = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr"></span> '
			. '<span class="wb-itemlink-id">(' . self::ITEM_WITHOUT_LABEL . ')</span></span>';

		$this->assertEquals( $expected, $html );
		$this->assertContains( self::ITEM_WITHOUT_LABEL, $customAttribs['title'] );
	}

	private function getOutputPage( Title $title ) {
		$context = RequestContext::newExtraneousContext( $title );
		return $context->getOutput();
	}

	/**
	 * @return PageEntityIdLookup
	 */
	private function getPageEntityIdLookup() {
		$entityIdLookup = $this->getMock( 'Wikibase\Repo\Store\PageEntityIdLookup' );

		$entityIdLookup->expects( $this->any() )
			->method( 'getPageEntityId' )
			->will( $this->returnCallback( function( Title $title ) {
				if ( preg_match( '/^Q(\d+)$/', $title->getText(), $m ) ) {
					return new ItemId( $m[0] );
				}

				return null;
			} ) );

		return $entityIdLookup;
	}

	/**
	 * @return TermLookup
	 */
	private function getTermLookup() {
		$termLookup = $this->getMock( 'Wikibase\Lib\Store\TermLookup' );

		$termLookup->expects( $this->any() )
			->method( 'getLabels' )
			->will( $this->returnCallback( function ( EntityId $id ) {
				if ( $id->getSerialization() == LinkBeginHookHandlerTest::ITEM_WITH_LABEL ) {
					return array( 'en' => 'linkbegin-label' );
				}

				if ( $id->getSerialization() == LinkBeginHookHandlerTest::ITEM_WITHOUT_LABEL ) {
					return array();
				}

				throw new StorageException( 'No such entity: ' . $id->getSerialization() );
			} ) );

		$termLookup->expects( $this->any() )
			->method( 'getDescriptions' )
			->will( $this->returnCallback( function ( EntityId $id ) {
				if ( $id->getSerialization() == LinkBeginHookHandlerTest::ITEM_WITH_LABEL ) {
					return array( 'en' => 'linkbegin-description' );
				}


				if ( $id->getSerialization() == LinkBeginHookHandlerTest::ITEM_WITHOUT_LABEL ) {
					return array();
				}

				throw new StorageException( 'No such entity: ' . $id->getSerialization() );
			} ) );

		return $termLookup;
	}

	private function getLinkBeginHookHandler() {
		$languageFallback = new LanguageFallbackChain( array(
			LanguageWithConversion::factory( 'de-ch' ),
			LanguageWithConversion::factory( 'de' ),
			LanguageWithConversion::factory( 'en' ),
		) );

		return new LinkBeginHookHandler(
			$this->getPageEntityIdLookup(),
			$this->getTermLookup(),
			$languageFallback,
			Language::factory( 'en' )
		);

	}
}
