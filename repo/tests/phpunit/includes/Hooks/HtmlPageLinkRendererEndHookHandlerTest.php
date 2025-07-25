<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\Hooks;

use HtmlArmor;
use MediaWiki\Context\RequestContext;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MainConfigNames;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\Hooks\HtmlPageLinkRendererEndHookHandler
 *
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class HtmlPageLinkRendererEndHookHandlerTest extends HtmlPageLinkRendererEndHookHandlerTestBase {

	protected const NAMESPACE_PROPERTY = 122;

	/**
	 * @dataProvider validLinkRendererAndContextProvider
	 */
	public function testDoHtmlPageLinkRendererBegin_validContext( LinkRenderer $linkRenderer, RequestContext $context ) {
		$handler = $this->newInstance();

		$title = $this->newTitle( self::ITEM_WITH_LABEL );
		$text = $title->getFullText();
		$customAttribs = [];

		$ret = $handler->doHtmlPageLinkRendererEnd(
			$linkRenderer, $title, $text, $customAttribs, $context );

		$expectedHtml = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr">' . self::DUMMY_LABEL . '</span> '
			. '<span class="wb-itemlink-id">(' . self::ITEM_WITH_LABEL . ')</span></span>';

		$this->assertTrue( $ret );
		$this->assertInstanceOf( HtmlArmor::class, $text );
		$this->assertEquals( $expectedHtml, HtmlArmor::getHtml( $text ) );

		$this->assertStringContainsString( self::DUMMY_LABEL, $customAttribs['title'] );
		$this->assertStringContainsString( self::DUMMY_DESCRIPTION, $customAttribs['title'] );

		$this->assertContains( 'wikibase.alltargets', $context->getOutput()->getModuleStyles() );
	}

	/**
	 * @dataProvider invalidLinkRendererAndContextProvider
	 */
	public function testDoHtmlPageLinkRendererBegin_invalidContext( LinkRenderer $linkRenderer, RequestContext $context ) {
		$handler = $this->newInstance();

		$title = $this->newTitle( self::ITEM_WITH_LABEL );
		$titleText = $title->getFullText();
		$text = $titleText;
		$customAttribs = [];

		$ret = $handler->doHtmlPageLinkRendererEnd(
			$linkRenderer, $title, $text, $customAttribs, $context );

		$this->assertTrue( $ret );
		$this->assertEquals( $titleText, $text );
		$this->assertEquals( [], $customAttribs );
	}

	public static function overrideSpecialNewEntityLinkProvider(): iterable {
		$entityContentFactory = WikibaseRepo::getEntityContentFactory();
		$namespaceLookup = self::getEntityNamespaceLookup();

		foreach ( $entityContentFactory->getEntityTypes() as $entityType ) {
			$entityHandler = $entityContentFactory->getContentHandlerForType( $entityType );
			$specialPage = $entityHandler->getSpecialPageForCreation();

			if ( $specialPage !== null ) {
				$ns = $namespaceLookup->getEntityNamespace( $entityType );
				yield [ $specialPage, $ns ];
			}
		}
	}

	/**
	 * @dataProvider overrideSpecialNewEntityLinkProvider
	 */
	public function testDoHtmlPageLinkRendererBegin_overrideSpecialNewEntityLink(
		string $linkTitle,
		int $ns
	) {
		$handler = $this->newInstance();

		$title = Title::makeTitle( $ns, $linkTitle );
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

	public static function noOverrideSpecialNewEntityLinkProvider(): iterable {
		$lookup = self::getEntityNamespaceLookup();
		$itemNs = $lookup->getEntityNamespace( 'item' );
		$propertyNs = $lookup->getEntityNamespace( 'property' );
		return [
			'NS=ITEM, title=Log' => [ 'Log', $itemNs ],
			'NS=PROPERTY, TITLE=Log' => [ 'Log', $propertyNs ],
			'NS=ITEM, title=NewProperty' => [ 'NewProperty', $itemNs ],
			'NS=PROPERTY, title=NewItem' => [ 'NewItem', $propertyNs ],
			'EXTERNAL title Log' => [ 'Log', NS_MAIN, 'w' ],
			'EXTERNAL title NewItem' => [ 'NewItem', NS_MAIN, self::FOREIGN_REPO_PREFIX ],
		];
	}

	/**
	 * @dataProvider noOverrideSpecialNewEntityLinkProvider
	 */
	public function testDoHtmlPageLinkRendererBegin_avoidSpecialPageReplacement(
		string $linkText,
		int $ns,
		string $interwiki = ''
	) {
		$handler = $this->newInstance();

		$title = Title::makeTitle( $ns, $linkText, '', $interwiki );

		$text = $title->getFullText();
		$context = $this->newContext();
		$attribs = [];
		$html = null;

		$ret = $handler->doHtmlPageLinkRendererEnd(
			$this->getLinkRenderer(), $title, $text, $attribs, $context, $html );

		$this->assertTrue( $ret );
		$this->assertNull( $html );
		$this->assertEquals( $title->getFullText(), $text );
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

	public function testDoHtmlPageLinkRendererBegin_itemWithNamespace_labelIsUsed() {
		$handler = $this->newInstance();

		$title = $this->newTitle( self::PROPERTY_WITH_LABEL, true, self::NAMESPACE_PROPERTY );
		$text = $title->getFullText();
		$customAttribs = [];

		$context = $this->newContext();
		$ret = $handler->doHtmlPageLinkRendererEnd(
			$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$expected = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr">' . self::DUMMY_LABEL . '</span> '
			. '<span class="wb-itemlink-id">(' . self::PROPERTY_WITH_LABEL . ')</span></span>';

		$lang = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' );
		$this->assertTrue( $ret );
		$this->assertInstanceOf( HtmlArmor::class, $text );
		$this->assertEquals( $expected, HtmlArmor::getHtml( $text ) );
		$this->assertEquals(
			$lang->getDirMark() . 'linkbegin-label' . $lang->getDirMark(),
			$customAttribs['title']
		);
	}

	public function testDoHtmlPageLinkRendererBegin_itemWithNamespace_labelIsUsedIfPagenameWithoutNamespaceSupplied() {
		$handler = $this->newInstance();

		$title = $this->newTitle( self::PROPERTY_WITH_LABEL, true, self::NAMESPACE_PROPERTY );
		$text = $title->getText();
		$customAttribs = [];

		$context = $this->newContext();
		$ret = $handler->doHtmlPageLinkRendererEnd(
			$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$expected = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr">' . self::DUMMY_LABEL . '</span> '
			. '<span class="wb-itemlink-id">(' . self::PROPERTY_WITH_LABEL . ')</span></span>';

		$lang = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' );
		$this->assertTrue( $ret );
		$this->assertEquals( $expected, HtmlArmor::getHtml( $text ) );
		$this->assertEquals(
			$lang->getDirMark() . 'linkbegin-label' . $lang->getDirMark(),
			$customAttribs['title']
		);
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

		$lang = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' );
		$this->assertTrue( $ret );
		$this->assertInstanceOf( HtmlArmor::class, $text );
		$this->assertEquals( $expected, HtmlArmor::getHtml( $text ) );
		$this->assertEquals(
			$lang->getDirMark() . 'linkbegin-label' . $lang->getDirMark(),
			$customAttribs['title']
		);
	}

	public function testDoHtmlPageLinkRendererBegin_itemIsRedirected() {
		$handler = $this->newInstance();
		$title = $this->newTitle( self::ITEM_LABEL_NO_DESCRIPTION );
		$title->mRedirect = true;
		$text = $title->getFullText();
		$customAttribs = [];
		$context = $this->newContext();

		$entityUrl = 'http://www.wikidata.org/wiki/Item:Q1';
		$expectedHref = $entityUrl . '?redirect=no';
		$this->entityUrlLookup->expects( $this->once() )
			->method( 'getLinkUrl' )
			->willReturn( $entityUrl );

		$ret = $handler->doHtmlPageLinkRendererEnd(
		$this->getLinkRenderer(), $title, $text, $customAttribs, $context );

		$this->assertTrue( $ret );
		$this->assertSame( $expectedHref, $customAttribs['href'] );
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

	public static function entityUrlProvider(): iterable {
		yield 'existing entity' => [
			'entityId' => self::ITEM_WITH_LABEL,
			'expectedUrl' => 'some-wiki/wiki/Item:' . self::ITEM_WITH_LABEL,
			'isDeleted' => false,
		];
		yield 'deleted entity' => [
			'entityId' => self::ITEM_DELETED,
			'expectedUrl' => 'some-wiki/wiki/Item:' . self::ITEM_DELETED,
			'isDeleted' => true,
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
	 * @dataProvider linkTargetProvider
	 */
	public function testExtractForeignIdString(
		?int $namespace,
		string $linkTargetText,
		?string $expectedOutput
	) {
		$wrapper = TestingAccessWrapper::newFromObject( $this->newInstance() );
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'interwiki' )
			->ignore()
			->row( [
				'iw_prefix' => 'metawikimedia',
				'iw_url' => "https://example.com/wiki/$1",
				'iw_api' => '',
				'iw_wikiid' => '',
				'iw_local' => false,
			] )
			->caller( __METHOD__ )
			->execute();

		$this->overrideConfigValue( MainConfigNames::ExtraInterlanguageLinkPrefixes, [ 'madeuplanguage' ] );
		$linkTarget = $namespace !== null ?
			Title::makeTitle( $namespace, $linkTargetText ) :
			Title::newFromTextThrow( $linkTargetText );
		$output = $wrapper->extractForeignIdString( $linkTarget );
		$this->assertSame( $expectedOutput, $output );
	}

	public static function linkTargetProvider(): iterable {
		return [
			'NS=MAIN, title=null' => [ NS_MAIN, 'ignored', null ], // T260853
			'NS=SPECIAL, title=null' => [ NS_SPECIAL, 'ignored', null ],
			'NS=SPECIAL, title=EntityPage/Q123' => [ null, 'Special:EntityPage/Q123', 'Q123' ],
			// One of the defaults from MediaWiki's maintenance/interwiki.list (but not Wikidata, as this might be the local test wiki name)
			'NS=MAIN, title=Special:EntityPage/Q123' => [ null, 'metawikimedia:Special:EntityPage/Q123', 'Q123' ],
		];
	}

}
