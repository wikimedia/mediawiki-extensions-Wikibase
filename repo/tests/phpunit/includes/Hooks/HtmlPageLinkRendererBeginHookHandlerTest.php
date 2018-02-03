<?php

namespace Wikibase\Repo\Tests\Hooks;

use HtmlArmor;
use Language;
use MediaWikiTestCase;
use MediaWiki\Interwiki\InterwikiLookup;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MediaWikiServices;
use RequestContext;
use SpecialPageFactory;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatterFactory;
use Wikibase\Repo\Hooks\HtmlPageLinkRendererBeginHookHandler;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\EntityIdLookup;

/**
 * @covers Wikibase\Repo\Hooks\HtmlPageLinkRendererBeginHookHandler
 *
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class HtmlPageLinkRendererBeginHookHandlerTest extends MediaWikiTestCase {

	const ITEM_WITH_LABEL = 'Q1';
	const ITEM_WITHOUT_LABEL = 'Q11';
	const ITEM_DELETED = 'Q111';
	const ITEM_LABEL_NO_DESCRIPTION = 'Q1111';
	const ITEM_FOREIGN = 'foo:Q2';
	const ITEM_FOREIGN_NO_DATA = 'foo:Q22';
	const ITEM_FOREIGN_NO_PREFIX = 'Q2';
	const ITEM_FOREIGN_NO_DATA_NO_PREFIX = 'Q22';

	const FOREIGN_REPO_PREFIX = 'foo';
	const UNKNOWN_FOREIGN_REPO = 'bar';

	const DUMMY_LABEL = 'linkbegin-label';
	const DUMMY_LABEL_FOREIGN_ITEM = 'linkbegin-foreign-item-label';

	const DUMMY_DESCRIPTION = 'linkbegin-description';
	const DUMMY_DESCRIPTION_FOREIGN_ITEM = 'linkbegin-foreign-item-description';

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
	public function testDoHtmlPageLinkRendererBegin_validContext( RequestContext $context ) {
		$handler = $this->newInstance();

		$title = $this->newTitle( self::ITEM_WITH_LABEL );
		$text = $title->getFullText();
		$customAttribs = [];

		$ret = $handler->doHtmlPageLinkRendererBegin(
			$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$expectedHtml = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr">' . self::DUMMY_LABEL . '</span> '
			. '<span class="wb-itemlink-id">(' . self::ITEM_WITH_LABEL . ')</span></span>';

		$this->assertTrue( $ret );
		$this->assertInstanceOf( HtmlArmor::class, $text );
		$this->assertEquals( $expectedHtml, HtmlArmor::getHtml( $text ) );

		$this->assertContains( self::DUMMY_LABEL, $customAttribs['title'] );
		$this->assertContains( self::DUMMY_DESCRIPTION, $customAttribs['title'] );

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
	public function testDoHtmlPageLinkRendererBegin_invalidContext( RequestContext $context ) {
		$handler = $this->newInstance();

		$title = $this->newTitle( self::ITEM_WITH_LABEL );
		$titleText = $title->getFullText();
		$text = $titleText;
		$customAttribs = [];

		$ret = $handler->doHtmlPageLinkRendererBegin(
			$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$this->assertTrue( $ret );
		$this->assertEquals( $titleText, $text );
		$this->assertEquals( [], $customAttribs );
	}

	public function overrideSpecialNewEntityLinkProvider() {
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
		$linkTitles = [];

		foreach ( $entityContentFactory->getEntityTypes() as $entityType ) {
			$entityHandler = $entityContentFactory->getContentHandlerForType( $entityType );
			$specialPage = $entityHandler->getSpecialPageForCreation();

			if ( $specialPage !== null ) {
				$linkTitles[] = [ $specialPage ];
			}
		}

		return $linkTitles;
	}

	/**
	 * @dataProvider overrideSpecialNewEntityLinkProvider
	 * @param string $linkTitle
	 */
	public function testDoHtmlPageLinkRendererBegin_overrideSpecialNewEntityLink( $linkTitle ) {
		$handler = $this->newInstance();

		$title = Title::makeTitle( NS_MAIN, $linkTitle );
		$text = $title->getFullText();
		$context = $this->newContext();
		$attribs = [];
		$html = null;

		$ret = $handler->doHtmlPageLinkRendererBegin(
			$this->getLinkRenderer(), $title, $text, $attribs, $context, $html );

		$specialPageTitle = Title::makeTitle(
			NS_SPECIAL,
			SpecialPageFactory::getLocalNameFor( $linkTitle )
		);

		$this->assertFalse( $ret );
		$this->assertContains(
			$this->getLinkRenderer()->makeKnownLink( $specialPageTitle ),
			$html
		);
		$this->assertContains( $specialPageTitle->getFullText(), $html );
	}

	public function testDoHtmlPageLinkRendererBegin_nonEntityTitleLink() {
		$handler = $this->newInstance();

		$title = Title::newMainPage();
		$title->resetArticleID( 1 );
		$this->assertTrue( $title->exists() ); // sanity check

		$titleText = $title->getFullText();
		$text = $titleText;
		$customAttribs = [];

		$context = $this->newContext();
		$ret = $handler->doHtmlPageLinkRendererBegin(
			$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$this->assertTrue( $ret );
		$this->assertEquals( $titleText, $text );
		$this->assertEquals( [], $customAttribs );
	}

	public function testDoHtmlPageLinkRendererBegin_unknownEntityTitle() {
		$handler = $this->newInstance();

		$title = $this->newTitle( self::ITEM_DELETED, false );
		$titleText = $title->getFullText();
		$text = $titleText;
		$customAttribs = [];

		$context = $this->newContext();
		$ret = $handler->doHtmlPageLinkRendererBegin(
			$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$this->assertTrue( $ret );
		$this->assertEquals( $titleText, $text );
		$this->assertEquals( [], $customAttribs );
	}

	public function testDoHtmlPageLinkRendererBegin_itemHasNoLabel() {
		$handler = $this->newInstance();

		$title = $this->newTitle( self::ITEM_WITHOUT_LABEL );
		$text = $title->getFullText();
		$customAttribs = [];

		$context = $this->newContext();
		$ret = $handler->doHtmlPageLinkRendererBegin(
			$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$expected = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr"></span> '
			. '<span class="wb-itemlink-id">(' . self::ITEM_WITHOUT_LABEL . ')</span></span>';

		$this->assertTrue( $ret );
		$this->assertInstanceOf( HtmlArmor::class, $text );
		$this->assertEquals( $expected, HtmlArmor::getHtml( $text ) );
		$this->assertContains( self::ITEM_WITHOUT_LABEL, $customAttribs['title'] );
	}

	public function testDoHtmlPageLinkRendererBegin_itemHasNoDescription() {
		$handler = $this->newInstance();

		$title = $this->newTitle( self::ITEM_LABEL_NO_DESCRIPTION );
		$text = $title->getFullText();
		$customAttribs = [];

		$context = $this->newContext();
		$ret = $handler->doHtmlPageLinkRendererBegin(
			$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$expected = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr">' . self::DUMMY_LABEL . '</span> '
			. '<span class="wb-itemlink-id">(' . self::ITEM_LABEL_NO_DESCRIPTION . ')</span></span>';

		$lang = Language::factory( 'en' );
		$this->assertTrue( $ret );
		$this->assertInstanceOf( HtmlArmor::class, $text );
		$this->assertEquals( $expected, HtmlArmor::getHtml( $text ) );
		$this->assertEquals(
			$lang->getDirMark() . 'linkbegin-label' . $lang->getDirMark(),
			$customAttribs['title']
		);
	}

	public function testGivenForeignIdWithLabelAndDescription_labelAndIdAreUsedAsLinkTextAndLabelAndDescriptionAreUsedInLinkTitle() {
		$handler = $this->newInstance();

		$title = Title::makeTitle(
			NS_MAIN,
			'Special:EntityPage/' . self::ITEM_FOREIGN_NO_PREFIX,
			'',
			self::FOREIGN_REPO_PREFIX
		);
		$text = $title->getFullText();
		$customAttribs = [];
		$context = $this->newContext();

		$ret = $handler->doHtmlPageLinkRendererBegin(
			$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$expectedHtml = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr">' . self::DUMMY_LABEL_FOREIGN_ITEM . '</span> '
			. '<span class="wb-itemlink-id">('
			. self::FOREIGN_REPO_PREFIX . ':' . self::ITEM_FOREIGN_NO_PREFIX
			. ')</span></span>';

		$this->assertTrue( $ret );
		$this->assertInstanceOf( HtmlArmor::class, $text );
		$this->assertSame( $expectedHtml, HtmlArmor::getHtml( $text ) );

		$this->assertContains( self::DUMMY_LABEL_FOREIGN_ITEM, $customAttribs['title'] );
		$this->assertContains( self::DUMMY_DESCRIPTION_FOREIGN_ITEM, $customAttribs['title'] );
	}

	public function testGivenForeignIdWithoutLabelAndDescription_idIsUsedAsLinkTextAndWikitextLinkIsUsedInLinkTitle() {
		$handler = $this->newInstance();

		$title = Title::makeTitle(
			NS_MAIN,
			'Special:EntityPage/' . self::ITEM_FOREIGN_NO_DATA_NO_PREFIX,
			'',
			self::FOREIGN_REPO_PREFIX
		);
		$text = $title->getFullText();
		$customAttribs = [];
		$context = $this->newContext();

		$ret = $handler->doHtmlPageLinkRendererBegin(
			$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$expectedHtml = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr"></span> '
			. '<span class="wb-itemlink-id">('
			. self::FOREIGN_REPO_PREFIX . ':' . self::ITEM_FOREIGN_NO_DATA_NO_PREFIX
			. ')</span></span>';

		$this->assertTrue( $ret );
		$this->assertInstanceOf( HtmlArmor::class, $text );
		$this->assertSame( $expectedHtml, HtmlArmor::getHtml( $text ) );

		$this->assertSame(
			self::FOREIGN_REPO_PREFIX . ':Special:EntityPage/' . self::ITEM_FOREIGN_NO_DATA_NO_PREFIX,
			$customAttribs['title']
		);
	}

	public function testGivenEntityPageOnUnknownForeignRepo_entityPageIsUsedAsLinkTextAndThereIsNoLinkTitle() {
		$handler = $this->newInstance();

		$title = Title::makeTitle(
			NS_MAIN,
			'Special:EntityPage/' . self::ITEM_FOREIGN_NO_PREFIX,
			'',
			self::UNKNOWN_FOREIGN_REPO
		);
		$text = $title->getFullText();
		$customAttribs = [];
		$context = $this->newContext();

		$ret = $handler->doHtmlPageLinkRendererBegin(
			$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$this->assertTrue( $ret );
		$this->assertSame(
			self::UNKNOWN_FOREIGN_REPO . ':Special:EntityPage/' . self::ITEM_FOREIGN_NO_PREFIX,
			$text
		);

		$this->assertArrayNotHasKey( 'title', $customAttribs );
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
						return [ 'en' => self::DUMMY_LABEL ];
					case self::ITEM_WITHOUT_LABEL:
						return [];
					case self::ITEM_FOREIGN:
						return [ 'en' => self::DUMMY_LABEL_FOREIGN_ITEM ];
					case self::ITEM_FOREIGN_NO_DATA:
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
						return [ 'en' => self::DUMMY_DESCRIPTION ];
					case self::ITEM_WITHOUT_LABEL:
					case self::ITEM_LABEL_NO_DESCRIPTION:
						return [];
					case self::ITEM_FOREIGN:
						return [ 'en' => self::DUMMY_DESCRIPTION_FOREIGN_ITEM ];
					case self::ITEM_FOREIGN_NO_DATA:
						return [];
					default:
						throw new StorageException( "Unexpected entity id $id" );
				}
			} ) );

		return $termLookup;
	}

	private function getEntityNamespaceLookup() {
		$entityNamespaces = [
			'item' => 0,
			'property' => 102
		];

		return new EntityNamespaceLookup( $entityNamespaces );
	}

	private function getInterwikiLookup() {
		$lookup = $this->getMock( InterwikiLookup::class );
		$lookup->expects( $this->any() )
			->method( 'isValidInterwiki' )
			->will(
				$this->returnCallback( function( $interwiki ) {
					return $interwiki === self::FOREIGN_REPO_PREFIX;
				} )
			);
		return $lookup;
	}

	/**
	 * @return LinkRenderer
	 */
	private function getLinkRenderer() {
		return MediaWikiServices::getInstance()->getLinkRenderer();
	}

	private function newInstance() {
		$languageFallback = new LanguageFallbackChain( [
			LanguageWithConversion::factory( 'de-ch' ),
			LanguageWithConversion::factory( 'de' ),
			LanguageWithConversion::factory( 'en' ),
		] );

		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
			$this->getTermLookup(),
			$languageFallback
		);

		return new HtmlPageLinkRendererBeginHookHandler(
			$this->getEntityIdLookup(),
			new ItemIdParser(),
			$labelDescriptionLookup,
			$this->getEntityNamespaceLookup(),
			$this->getInterwikiLookup(),
			$this->getEntityLinkFormatterFactory()
		);
	}

	private function getEntityLinkFormatterFactory() {
		return new EntityLinkFormatterFactory( Language::factory( 'en' ), [
			'item' => function( $language ) {
				return new DefaultEntityLinkFormatter( $language );
			},
		] );
	}

}
