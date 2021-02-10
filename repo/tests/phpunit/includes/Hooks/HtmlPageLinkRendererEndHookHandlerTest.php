<?php

namespace Wikibase\Repo\Tests\Hooks;

use HtmlArmor;
use Language;
use MediaWiki\Interwiki\InterwikiLookup;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use RequestContext;
use SpecialPage;
use Title;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\Lib\LanguageFallbackChain;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\LanguageWithConversion;
use Wikibase\Lib\Store\EntityExistenceChecker;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityLinkTargetEntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatterFactory;
use Wikibase\Repo\Hooks\HtmlPageLinkRendererEndHookHandler;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Hooks\HtmlPageLinkRendererEndHookHandler
 *
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class HtmlPageLinkRendererEndHookHandlerTest extends MediaWikiIntegrationTestCase {

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

	private $entityUrlLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->entityUrlLookup = $this->createMock( EntityUrlLookup::class );
	}

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

		$ret = $handler->doHtmlPageLinkRendererEnd(
			$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$expectedHtml = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr">' . self::DUMMY_LABEL . '</span> '
			. '<span class="wb-itemlink-id">(' . self::ITEM_WITH_LABEL . ')</span></span>';

		$this->assertTrue( $ret );
		$this->assertInstanceOf( HtmlArmor::class, $text );
		$this->assertEquals( $expectedHtml, HtmlArmor::getHtml( $text ) );

		$this->assertStringContainsString( self::DUMMY_LABEL, $customAttribs['title'] );
		$this->assertStringContainsString( self::DUMMY_DESCRIPTION, $customAttribs['title'] );

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

		$ret = $handler->doHtmlPageLinkRendererEnd(
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

		$ret = $handler->doHtmlPageLinkRendererEnd(
			$this->getLinkRenderer(), $title, $text, $attribs, $context, $html );

		$specialPageTitle = SpecialPage::getTitleFor( $linkTitle );

		$this->assertFalse( $ret );
		$this->assertStringContainsString(
			$this->getLinkRenderer()->makeKnownLink( $specialPageTitle ),
			$html
		);
		$this->assertStringContainsString( $specialPageTitle->getFullText(), $html );
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
		$ret = $handler->doHtmlPageLinkRendererEnd(
			$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$this->assertTrue( $ret );
		$this->assertEquals( $titleText, $text );
		$this->assertEquals( [], $customAttribs );
	}

	public function testDoHtmlPageLinkRendererBegin_deleteItem() {
		$handler = $this->newInstance( "foo", true );

		$title = $this->newTitle( self::ITEM_DELETED, false );
		$titleText = $title->getFullText();
		$text = $titleText;
		$customAttribs = [];

		$context = $this->newContext();
		$ret = $handler->doHtmlPageLinkRendererEnd(
			$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$this->assertTrue( $ret );
		$this->assertEquals( $titleText, $text );
	}

	public function testDoHtmlPageLinkRendererBegin_itemHasNoLabel() {
		$handler = $this->newInstance( "Item:Q11", false );

		$title = $this->newTitle( self::ITEM_WITHOUT_LABEL );
		$text = $title->getFullText();
		$customAttribs = [];

		$context = $this->newContext();
		$ret = $handler->doHtmlPageLinkRendererEnd(
			$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$expected = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr"></span> '
			. '<span class="wb-itemlink-id">(' . self::ITEM_WITHOUT_LABEL . ')</span></span>';

		$this->assertTrue( $ret );
		$this->assertInstanceOf( HtmlArmor::class, $text );
		$this->assertEquals( $expected, HtmlArmor::getHtml( $text ) );
		$this->assertArrayHasKey( 'title', $customAttribs );
		$this->assertNotNull( $customAttribs['title'] );
		$this->assertStringContainsString( self::ITEM_WITHOUT_LABEL, $customAttribs['title'] );
	}

	public function testDoHtmlPageLinkRendererBegin_itemHasNoDescription() {
		$handler = $this->newInstance();

		$title = $this->newTitle( self::ITEM_LABEL_NO_DESCRIPTION );
		$text = $title->getFullText();
		$customAttribs = [];

		$context = $this->newContext();
		$ret = $handler->doHtmlPageLinkRendererEnd(
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

	public function testGivenIdFromOtherSourcesWithLabelAndDesc_labelAndIdAreUsedAsLinkTextAndLabelAndDescAreUsedInLinkTitle() {
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

		$ret = $handler->doHtmlPageLinkRendererEnd(
			$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$expectedHtml = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr">' . self::DUMMY_LABEL_FOREIGN_ITEM . '</span> '
			. '<span class="wb-itemlink-id">('
			. self::ITEM_FOREIGN_NO_PREFIX
			. ')</span></span>';

		$this->assertTrue( $ret );
		$this->assertInstanceOf( HtmlArmor::class, $text );
		$this->assertSame( $expectedHtml, HtmlArmor::getHtml( $text ) );

		$this->assertStringContainsString( self::DUMMY_LABEL_FOREIGN_ITEM, $customAttribs['title'] );
		$this->assertStringContainsString( self::DUMMY_DESCRIPTION_FOREIGN_ITEM, $customAttribs['title'] );
	}

	public function testGivenIdFromOtherSourceWithoutLabelAndDesc_idIsUsedAsLinkTextAndWikitextLinkIsUsedInLinkTitle() {
		$prefixedText = 'expectedPrefixedText';
		$handler = $this->newInstance( $prefixedText );

		$title = Title::makeTitle(
			NS_MAIN,
			'Special:EntityPage/' . self::ITEM_FOREIGN_NO_DATA_NO_PREFIX,
			'',
			self::FOREIGN_REPO_PREFIX
		);
		$text = $title->getFullText();
		$customAttribs = [];
		$context = $this->newContext();

		$ret = $handler->doHtmlPageLinkRendererEnd(
			$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$expectedHtml = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr"></span> '
			. '<span class="wb-itemlink-id">('
			. self::ITEM_FOREIGN_NO_DATA_NO_PREFIX
			. ')</span></span>';

		$this->assertTrue( $ret );
		$this->assertInstanceOf( HtmlArmor::class, $text );
		$this->assertSame( $expectedHtml, HtmlArmor::getHtml( $text ) );

		$this->assertSame(
			$prefixedText,
			$customAttribs['title']
		);
	}

	public function testGivenEntityPageOnUnknownEntitySource_entityPageIsUsedAsLinkTextAndThereIsNoLinkTitle() {
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

		$ret = $handler->doHtmlPageLinkRendererEnd(
			$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$this->assertTrue( $ret );
		$this->assertSame(
			self::UNKNOWN_FOREIGN_REPO . ':Special:EntityPage/' . self::ITEM_FOREIGN_NO_PREFIX,
			$text
		);

		$this->assertArrayNotHasKey( 'title', $customAttribs );
	}

	/**
	 * @dataProvider entityUrlProvider
	 */
	public function testOverridesUrlForEntityLinks( string $entityId, string $expectedUrl, bool $isDeleted ) {
		$customAttribs = [ 'href' => 'will-be-overridden' ];

		$this->entityUrlLookup = $this->createMock( EntityUrlLookup::class );
		$this->entityUrlLookup->expects( $this->once() )
			->method( 'getLinkUrl' )
			->with( $this->callback( function ( $id ) use ( $entityId ) {
				$this->assertSame( $entityId, $id->getSerialization() );
				return true;
			} ) )
			->willReturn( $expectedUrl );

		$context = $this->newContext();
		$this->newInstance( null, $isDeleted )->doHtmlPageLinkRendererEnd(
			$this->getLinkRenderer(),
			$this->newTitle( $entityId ),
			$text,
			$customAttribs,
			$context
		);

		$this->assertEquals( $expectedUrl, $customAttribs['href'] );
	}

	public function entityUrlProvider() {
		yield 'existing entity' => [
			'entityId' => self::ITEM_WITH_LABEL,
			'expectedUrl' => 'some-wiki/wiki/Item:' . self::ITEM_WITH_LABEL,
			'isDeleted' => false
		];
		yield 'deleted entity' => [
			'entityId' => self::ITEM_DELETED,
			'expectedUrl' => 'some-wiki/wiki/Item:' . self::ITEM_DELETED,
			'isDeleted' => true
		];
	}

	public function testRemovesRedLinkClassForExistingEntities() {
		$customAttribs = [ 'class' => 'new some-other-class' ];

		$context = $this->newContext();
		$this->newInstance()->doHtmlPageLinkRendererEnd(
			$this->getLinkRenderer(),
			$this->newTitle( self::ITEM_WITH_LABEL ),
			$text,
			$customAttribs,
			$context
		);

		$this->assertEquals( 'some-other-class', $customAttribs['class'] );
	}

	/**
	 * @return EntityIdLookup
	 */
	private function getEntityIdLookup() {
		$entityIdLookup = $this->createMock( EntityIdLookup::class );

		// TODO fixme or use the real one maybe?
		$entityIdLookup->expects( $this->any() )
			->method( 'getEntityIdForTitle' )
			->will( $this->returnCallback( function( Title $title ) {
				if ( preg_match( '/(^|EntityPage\/)(Q\d+)$/', $title->getText(), $m ) ) {
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
		$termLookup = $this->createMock( TermLookup::class );

		$termLookup->expects( $this->any() )
			->method( 'getLabels' )
			->will( $this->returnCallback( function ( EntityId $id ) {
				switch ( $id->getSerialization() ) {
					case self::ITEM_WITH_LABEL:
					case self::ITEM_LABEL_NO_DESCRIPTION:
						return [ 'en' => self::DUMMY_LABEL ];
					case self::ITEM_WITHOUT_LABEL:
						return [];
					case self::ITEM_FOREIGN_NO_PREFIX:
						return [ 'en' => self::DUMMY_LABEL_FOREIGN_ITEM ];
					case self::ITEM_FOREIGN_NO_DATA_NO_PREFIX:
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
					case self::ITEM_FOREIGN_NO_PREFIX:
						return [ 'en' => self::DUMMY_DESCRIPTION_FOREIGN_ITEM ];
					case self::ITEM_FOREIGN_NO_DATA_NO_PREFIX:
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
		$lookup = $this->createMock( InterwikiLookup::class );
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

	private function newInstance( $titleText = "foo", $isDeleted = false ) {
		$languageFallback = new LanguageFallbackChain( [
			LanguageWithConversion::factory( 'de-ch' ),
			LanguageWithConversion::factory( 'de' ),
			LanguageWithConversion::factory( 'en' ),
		] );
		$languageFallbackChainFactory = $this
			->createMock( LanguageFallbackChainFactory::class );

		$languageFallbackChainFactory->expects( $this->any() )
			->method( 'newFromContext' )
			->willReturn( $languageFallback );
		$entityIdParser = new ItemIdParser();

		return new HtmlPageLinkRendererEndHookHandler(
			$this->getEntityExistenceChecker( $isDeleted ),
			$this->getEntityIdLookup(),
			$entityIdParser,
			$this->getTermLookup(),
			$this->getEntityNamespaceLookup(),
			$this->getInterwikiLookup(),
			$this->getEntityLinkFormatterFactory( $titleText ),
			MediaWikiServices::getInstance()->getSpecialPageFactory(),
			$languageFallbackChainFactory,
			$this->entityUrlLookup,
			new EntityLinkTargetEntityIdLookup(
				$this->getEntityNamespaceLookup(),
				$entityIdParser,
				$this->newMockEntitySourceDefinitions(),
				$this->newMockEntitySource()
			)
		);
	}

	private function getEntityLinkFormatterFactory( $titleText ) {
		$titleTextLookup = $this->getEntityTitleTextLookup( $titleText );

		return new EntityLinkFormatterFactory( Language::factory( 'en' ), $titleTextLookup, [
			'item' => function( $language ) use ( $titleTextLookup ) {
				return new DefaultEntityLinkFormatter( $language, $titleTextLookup );
			},
		] );
	}

	private function getEntityExistenceChecker( $isDeleted ) {
		$entityExistenceChecker = $this->createMock( EntityExistenceChecker::class );

		$entityExistenceChecker->expects( $this->any() )
			->method( 'exists' )
			->willReturn( !$isDeleted );
		return $entityExistenceChecker;
	}

	private function getEntityTitleTextLookup( $titleText ) {
		$entityTitleTextLookup = $this->createMock( EntityTitleTextLookup::class );

		$entityTitleTextLookup->expects( $this->any() )
			->method( 'getPrefixedText' )
			->willReturn( $titleText );

		return $entityTitleTextLookup;
	}

	private function newMockEntitySourceDefinitions() {
		$foreignItemSource = $this->createMock( EntitySource::class );
		$foreignItemSource->expects( $this->any() )
			->method( 'getInterwikiPrefix' )
			->willReturn( self::FOREIGN_REPO_PREFIX );

		$sourceDefs = $this->createMock( EntitySourceDefinitions::class );
		$sourceDefs->expects( $this->any() )
			->method( 'getSourceForEntityType' )
			->with( Item::ENTITY_TYPE )
			->willReturn( $foreignItemSource );

		return $sourceDefs;
	}

	private function newMockEntitySource() {
		$entitySource = $this->createMock( EntitySource::class );
		$entitySource->expects( $this->any() )
			->method( 'getEntityTypes' )
			->willReturn( [ Item::ENTITY_TYPE, Property::ENTITY_TYPE ] );

		return $entitySource;
	}

}
