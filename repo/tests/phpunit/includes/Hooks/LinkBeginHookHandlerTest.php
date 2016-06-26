<?php

namespace Wikibase\Repo\Tests\Hooks;

use Language;
use Linker;
use RequestContext;
use SpecialPageFactory;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Hooks\LinkBeginHookHandler;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\EntityIdLookup;

/**
 * @covers Wikibase\Repo\Hooks\LinkBeginHookHandler
 *
 * @since 0.5
 *
 * @group Database
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author Marius Hoch < hoo@online.de >
 */
class LinkBeginHookHandlerTest extends \MediaWikiTestCase {

	const ITEM_WITH_LABEL = 'Q1';
	const ITEM_WITHOUT_LABEL = 'Q11';
	const ITEM_DELETED = 'Q111';
	const ITEM_LABEL_NO_DESCRIPTION = 'Q1111';

	public function validContextProvider() {
		$historyContext = RequestContext::newExtraneousContext(
			Title::newFromText( 'Foo' )
		);
		$historyContext->getRequest()->setVal( 'action', 'history' );

		$diffContext = RequestContext::newExtraneousContext(
			Title::newFromText( 'Foo' )
		);
		$diffContext->getRequest()->setVal( 'diff', 123 );

		return array(
			"Special page" => array(
				RequestContext::newExtraneousContext(
					Title::newFromText( 'Special:Recentchanges' )
				)
			),
			"Action history" => array( $historyContext ),
			"Diff" => array( $diffContext )
		);
	}

	/**
	 * @dataProvider validContextProvider
	 */
	public function testDoOnLinkBegin_validContext( RequestContext $context ) {
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = Title::makeTitle( NS_MAIN, self::ITEM_WITH_LABEL );
		$title->resetArticleID( 1 );
		$this->assertTrue( $title->exists() ); // sanity check

		$html = $title->getFullText();
		$customAttribs = array();

		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $context );

		$expectedHtml = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr">linkbegin-label</span> '
			. '<span class="wb-itemlink-id">(' . self::ITEM_WITH_LABEL . ')</span></span>';

		$this->assertEquals( $expectedHtml, $html );

		$this->assertContains( 'linkbegin-label', $customAttribs['title'] );
		$this->assertContains( 'linkbegin-description', $customAttribs['title'] );

		$this->assertContains( 'wikibase.common', $context->getOutput()->getModuleStyles() );
	}

	public function invalidContextProvider() {
		$deleteContext = RequestContext::newExtraneousContext(
			Title::newFromText( 'Foo' )
		);
		$deleteContext->getRequest()->setVal( 'action', 'delete' );

		$diffNonViewContext = RequestContext::newExtraneousContext(
			Title::newFromText( 'Foo' )
		);
		$diffNonViewContext->getRequest()->setVal( 'action', 'protect' );
		$diffNonViewContext->getRequest()->setVal( 'diff', 123 );

		return array(
			"Action delete" => array( $deleteContext ),
			"Non-special page" => array( RequestContext::newExtraneousContext(
				Title::newFromText( 'Foo' )
			) ),
			"Edge case: diff parameter set, but action != view" => array(
				$diffNonViewContext
			)
		);
	}

	/**
	 * @dataProvider invalidContextProvider
	 */
	public function testDoOnLinkBegin_invalidContext( RequestContext $context ) {
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = Title::makeTitle( NS_MAIN, self::ITEM_WITH_LABEL );
		$title->resetArticleID( 1 );
		$this->assertTrue( $title->exists() ); // sanity check

		$titleText = $title->getFullText();
		$html = $titleText;
		$customAttribs = array();

		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $context );

		$this->assertEquals( $titleText, $html );
		$this->assertEquals( array(), $customAttribs );
	}

	public function overrideSpecialNewEntityLinkProvider() {
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
		$linkTitles = array();

		foreach ( $entityContentFactory->getEntityTypes() as $entityType ) {
			$entityHandler = $entityContentFactory->getContentHandlerForType( $entityType );
			$specialPage = $entityHandler->getSpecialPageForCreation();

			if ( $specialPage !== null ) {
				$linkTitles[] = array( $specialPage );
			}
		}

		return $linkTitles;
	}

	/**
	 * @dataProvider overrideSpecialNewEntityLinkProvider
	 * @param string $linkTitle
	 */
	public function testDoOnLinkBegin_overrideSpecialNewEntityLink( $linkTitle ) {
		$contextTitle = Title::newFromText( 'Special:Recentchanges' );
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = Title::makeTitle( NS_MAIN, $linkTitle );
		$html = $title->getFullText();
		$context = RequestContext::newExtraneousContext( $contextTitle );
		$attribs = array();

		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $attribs, $context );

		$specialPageTitle = Title::makeTitle(
			NS_SPECIAL,
			SpecialPageFactory::getLocalNameFor( $linkTitle )
		);

		$this->assertContains( Linker::linkKnown( $specialPageTitle ), $html );
		$this->assertContains( $specialPageTitle->getFullText(), $html );
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

		$context = RequestContext::newExtraneousContext( $contextTitle );
		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $context );

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

		$context = RequestContext::newExtraneousContext( $contextTitle );
		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $context );

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

		$context = RequestContext::newExtraneousContext( $contextTitle );
		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $context );

		$expected = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr"></span> '
			. '<span class="wb-itemlink-id">(' . self::ITEM_WITHOUT_LABEL . ')</span></span>';

		$this->assertEquals( $expected, $html );
		$this->assertContains( self::ITEM_WITHOUT_LABEL, $customAttribs['title'] );
	}

	public function testDoOnLinkBegin_itemHasNoDescription() {
		$contextTitle = Title::newFromText( 'Special:Recentchanges' );
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = Title::makeTitle( NS_MAIN, self::ITEM_LABEL_NO_DESCRIPTION );
		$title->resetArticleID( 1 );
		$this->assertTrue( $title->exists() ); // sanity check

		$html = $title->getFullText();
		$customAttribs = array();

		$context = RequestContext::newExtraneousContext( $contextTitle );
		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $context );

		$expected = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr">linkbegin-label</span> '
			. '<span class="wb-itemlink-id">(' . self::ITEM_LABEL_NO_DESCRIPTION . ')</span></span>';

		$this->assertEquals( $expected, $html );
		$this->assertEquals( 'linkbegin-label', $customAttribs['title'] );
	}

	/**
	 * @return EntityIdLookup
	 */
	private function getEntityIdLookup() {
		$entityIdLookup = $this->getMock( EntityIdLookup::class );

		$entityIdLookup->expects( $this->any() )
			->method( 'getEntityIdForTitle' )
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
		$termLookup = $this->getMock( TermLookup::class );

		$termLookup->expects( $this->any() )
			->method( 'getLabels' )
			->will( $this->returnCallback( function ( EntityId $id ) {
				if ( $id->getSerialization() == self::ITEM_WITH_LABEL ) {
					return array( 'en' => 'linkbegin-label' );
				}

				if ( $id->getSerialization() == self::ITEM_WITHOUT_LABEL ) {
					return array();
				}

				if ( $id->getSerialization() == self::ITEM_LABEL_NO_DESCRIPTION ) {
					return array( 'en' => 'linkbegin-label' );
				}

				throw new StorageException( 'No such entity: ' . $id->getSerialization() );
			} ) );

		$termLookup->expects( $this->any() )
			->method( 'getDescriptions' )
			->will( $this->returnCallback( function ( EntityId $id ) {
				if ( $id->getSerialization() == self::ITEM_WITH_LABEL ) {
					return array( 'en' => 'linkbegin-description' );
				}

				if ( $id->getSerialization() == self::ITEM_WITHOUT_LABEL ) {
					return array();
				}

				if ( $id->getSerialization() == self::ITEM_LABEL_NO_DESCRIPTION ) {
					return array();
				}

				throw new StorageException( 'No such entity: ' . $id->getSerialization() );
			} ) );

		return $termLookup;
	}

	private function getEntityNamespaceLookup() {
		$entityNamespaces = array(
			'item' => 0,
			'property' => 102
		);

		return new EntityNamespaceLookup( $entityNamespaces );
	}

	private function getLinkBeginHookHandler() {
		$languageFallback = new LanguageFallbackChain( array(
			LanguageWithConversion::factory( 'de-ch' ),
			LanguageWithConversion::factory( 'de' ),
			LanguageWithConversion::factory( 'en' ),
		) );

		return new LinkBeginHookHandler(
			$this->getEntityIdLookup(),
			$this->getTermLookup(),
			$this->getEntityNamespaceLookup(),
			$languageFallback,
			Language::factory( 'en' )
		);

	}

}
