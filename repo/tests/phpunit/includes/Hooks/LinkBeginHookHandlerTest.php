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
 * @author Thiemo Mättig
 */
class LinkBeginHookHandlerTest extends \MediaWikiTestCase {

	const ITEM_WITH_LABEL = 'Q1';
	const ITEM_WITHOUT_LABEL = 'Q11';
	const ITEM_DELETED = 'Q111';
	const ITEM_LABEL_NO_DESCRIPTION = 'Q1111';

	/**
	 * @param string $id
	 * @param bool $exists
	 *
	 * @return Title
	 */
	private function newTitle( $id, $exists = true ) {
		$title = Title::makeTitle( NS_MAIN, $id );
		$title->resetArticleID( $exists ? 1 : 0 );
		$this->assertSame( $exists, $title->exists(), 'Sanity check' );
		return $title;
	}

	/**
	 * @param string $title
	 *
	 * @return RequestContext
	 */
	private function newContext( $title = 'Special:Recentchanges' ) {
		return RequestContext::newExtraneousContext( Title::newFromText( $title ) );
	}

	public function validContextProvider() {
		$historyContext = $this->newContext( 'Foo' );
		$historyContext->getRequest()->setVal( 'action', 'history' );

		$diffContext = $this->newContext( 'Foo' );
		$diffContext->getRequest()->setVal( 'diff', 123 );

		return [
			'Special page' => [ $this->newContext() ],
			'Action history' => [ $historyContext ],
			'Diff' => [ $diffContext ],
		];
	}

	/**
	 * @dataProvider validContextProvider
	 */
	public function testDoOnLinkBegin_validContext( RequestContext $context ) {
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = $this->newTitle( self::ITEM_WITH_LABEL );
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
		$deleteContext = $this->newContext( 'Foo' );
		$deleteContext->getRequest()->setVal( 'action', 'delete' );

		$diffNonViewContext = $this->newContext( 'Foo' );
		$diffNonViewContext->getRequest()->setVal( 'action', 'protect' );
		$diffNonViewContext->getRequest()->setVal( 'diff', 123 );

		return [
			'Action delete' => [ $deleteContext ],
			'Non-special page' => [ $this->newContext( 'Foo' ) ],
			'Edge case: diff parameter set, but action != view' => [ $diffNonViewContext ],
		];
	}

	/**
	 * @dataProvider invalidContextProvider
	 */
	public function testDoOnLinkBegin_invalidContext( RequestContext $context ) {
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = $this->newTitle( self::ITEM_WITH_LABEL );
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
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = Title::makeTitle( NS_MAIN, $linkTitle );
		$html = $title->getFullText();
		$context = $this->newContext();
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
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = Title::newMainPage();
		$title->resetArticleID( 1 );
		$this->assertTrue( $title->exists() ); // sanity check

		$titleText = $title->getFullText();
		$html = $titleText;
		$customAttribs = array();

		$context = $this->newContext();
		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $context );

		$this->assertEquals( $titleText, $html );
		$this->assertEquals( array(), $customAttribs );
	}

	public function testDoOnLinkBegin_unknownEntityTitle() {
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = $this->newTitle( self::ITEM_DELETED, false );
		$titleText = $title->getFullText();
		$html = $titleText;
		$customAttribs = array();

		$context = $this->newContext();
		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $context );

		$this->assertEquals( $titleText, $html );
		$this->assertEquals( array(), $customAttribs );
	}

	public function testDoOnLinkBegin_itemHasNoLabel() {
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = $this->newTitle( self::ITEM_WITHOUT_LABEL );
		$html = $title->getFullText();
		$customAttribs = array();

		$context = $this->newContext();
		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $context );

		$expected = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr"></span> '
			. '<span class="wb-itemlink-id">(' . self::ITEM_WITHOUT_LABEL . ')</span></span>';

		$this->assertEquals( $expected, $html );
		$this->assertContains( self::ITEM_WITHOUT_LABEL, $customAttribs['title'] );
	}

	public function testDoOnLinkBegin_itemHasNoDescription() {
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = $this->newTitle( self::ITEM_LABEL_NO_DESCRIPTION );
		$html = $title->getFullText();
		$customAttribs = array();

		$context = $this->newContext();
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
				switch ( $id->getSerialization() ) {
					case self::ITEM_WITH_LABEL:
					case self::ITEM_LABEL_NO_DESCRIPTION:
						return [ 'en' => 'linkbegin-label' ];
					case self::ITEM_WITHOUT_LABEL:
						return [];
					default:
						throw new StorageException( "Unexpected entity id $id" );
				}
			} ) );

		$termLookup->expects( $this->any() )
			->method( 'getDescriptions' )
			->will( $this->returnCallback( function ( EntityId $id ) {
				switch ( $id->getSerialization() ) {
					case self::ITEM_WITH_LABEL:
						return [ 'en' => 'linkbegin-description' ];
					case self::ITEM_WITHOUT_LABEL:
					case self::ITEM_LABEL_NO_DESCRIPTION:
						return [];
					default:
						throw new StorageException( "Unexpected entity id $id" );
				}
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
