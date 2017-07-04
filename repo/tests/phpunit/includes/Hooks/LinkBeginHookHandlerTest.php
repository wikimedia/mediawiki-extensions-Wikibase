<?php

namespace Wikibase\Repo\Tests\Hooks;

use Language;
use MediaWiki\Interwiki\InterwikiLookup;
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
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Hooks\LinkBeginHookHandler;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\EntityIdLookup;

/**
 * @covers Wikibase\Repo\Hooks\LinkBeginHookHandler
 *
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class LinkBeginHookHandlerTest extends \MediaWikiTestCase {

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
	public function testDoOnLinkBegin_validContext( RequestContext $context ) {
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = $this->newTitle( self::ITEM_WITH_LABEL );
		$html = $title->getFullText();
		$customAttribs = [];

		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $context );

		$expectedHtml = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr">' . self::DUMMY_LABEL . '</span> '
			. '<span class="wb-itemlink-id">(' . self::ITEM_WITH_LABEL . ')</span></span>';

		$this->assertEquals( $expectedHtml, $html );

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
	public function testDoOnLinkBegin_invalidContext( RequestContext $context ) {
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = $this->newTitle( self::ITEM_WITH_LABEL );
		$titleText = $title->getFullText();
		$html = $titleText;
		$customAttribs = [];

		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $context );

		$this->assertEquals( $titleText, $html );
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
	public function testDoOnLinkBegin_overrideSpecialNewEntityLink( $linkTitle ) {
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = Title::makeTitle( NS_MAIN, $linkTitle );
		$html = $title->getFullText();
		$context = $this->newContext();
		$attribs = [];

		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $attribs, $context );

		$specialPageTitle = Title::makeTitle(
			NS_SPECIAL,
			SpecialPageFactory::getLocalNameFor( $linkTitle )
		);

		$this->assertContains(
			MediaWikiServices::getInstance()->getLinkRenderer()->makeKnownLink( $specialPageTitle ),
			$html
		);
		$this->assertContains( $specialPageTitle->getFullText(), $html );
	}

	public function testDoOnLinkBegin_nonEntityTitleLink() {
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = Title::newMainPage();
		$title->resetArticleID( 1 );
		$this->assertTrue( $title->exists() ); // sanity check

		$titleText = $title->getFullText();
		$html = $titleText;
		$customAttribs = [];

		$context = $this->newContext();
		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $context );

		$this->assertEquals( $titleText, $html );
		$this->assertEquals( [], $customAttribs );
	}

	public function testDoOnLinkBegin_unknownEntityTitle() {
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = $this->newTitle( self::ITEM_DELETED, false );
		$titleText = $title->getFullText();
		$html = $titleText;
		$customAttribs = [];

		$context = $this->newContext();
		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $context );

		$this->assertEquals( $titleText, $html );
		$this->assertEquals( [], $customAttribs );
	}

	public function testDoOnLinkBegin_itemHasNoLabel() {
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = $this->newTitle( self::ITEM_WITHOUT_LABEL );
		$html = $title->getFullText();
		$customAttribs = [];

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
		$customAttribs = [];

		$context = $this->newContext();
		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $context );

		$expected = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr">' . self::DUMMY_LABEL . '</span> '
			. '<span class="wb-itemlink-id">(' . self::ITEM_LABEL_NO_DESCRIPTION . ')</span></span>';

		$lang = Language::factory( 'en' );
		$this->assertEquals( $expected, $html );
		$this->assertEquals(
			$lang->getDirMark() . 'linkbegin-label' . $lang->getDirMark(),
			$customAttribs['title']
		);
	}

	public function testGivenForeignIdWithLabelAndDescription_labelAndIdAreUsedAsLinkTextAndLabelAndDescriptionAreUsedInLinkTitle() {
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = Title::makeTitle(
			NS_MAIN,
			'Special:EntityPage/' . self::ITEM_FOREIGN_NO_PREFIX,
			'',
			self::FOREIGN_REPO_PREFIX
		);
		$html = $title->getFullText();
		$customAttribs = [];

		$context = $this->newContext();

		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $context );

		$expectedHtml = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr">' . self::DUMMY_LABEL_FOREIGN_ITEM . '</span> '
			. '<span class="wb-itemlink-id">('
			. self::FOREIGN_REPO_PREFIX . ':' . self::ITEM_FOREIGN_NO_PREFIX
			. ')</span></span>';

		$this->assertSame( $expectedHtml, $html );

		$this->assertContains( self::DUMMY_LABEL_FOREIGN_ITEM, $customAttribs['title'] );
		$this->assertContains( self::DUMMY_DESCRIPTION_FOREIGN_ITEM, $customAttribs['title'] );
	}

	public function testGivenForeignIdWithoutLabelAndDescription_idIsUsedAsLinkTextAndWikitextLinkIsUsedInLinkTitle() {
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = Title::makeTitle(
			NS_MAIN,
			'Special:EntityPage/' . self::ITEM_FOREIGN_NO_DATA_NO_PREFIX,
			'',
			self::FOREIGN_REPO_PREFIX
		);
		$html = $title->getFullText();
		$customAttribs = [];

		$context = $this->newContext();

		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $context );

		$expectedHtml = '<span class="wb-itemlink">'
			. '<span class="wb-itemlink-label" lang="en" dir="ltr"></span> '
			. '<span class="wb-itemlink-id">('
			. self::FOREIGN_REPO_PREFIX . ':' . self::ITEM_FOREIGN_NO_DATA_NO_PREFIX
			. ')</span></span>';

		$this->assertSame( $expectedHtml, $html );

		$this->assertSame(
			self::FOREIGN_REPO_PREFIX . ':Special:EntityPage/' . self::ITEM_FOREIGN_NO_DATA_NO_PREFIX,
			$customAttribs['title']
		);
	}

	public function testGivenEntityPageOnUnknownForeignRepo_entityPageIsUsedAsLinkTextAndThereIsNoLinkTitle() {
		$linkBeginHookHandler = $this->getLinkBeginHookHandler();

		$title = Title::makeTitle(
			NS_MAIN,
			'Special:EntityPage/' . self::ITEM_FOREIGN_NO_PREFIX,
			'',
			self::UNKNOWN_FOREIGN_REPO
		);
		$html = $title->getFullText();
		$customAttribs = [];

		$context = $this->newContext();

		$linkBeginHookHandler->doOnLinkBegin( $title, $html, $customAttribs, $context );

		$this->assertSame(
			self::UNKNOWN_FOREIGN_REPO . ':Special:EntityPage/' . self::ITEM_FOREIGN_NO_PREFIX,
			$html
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

	private function getLinkBeginHookHandler() {
		$languageFallback = new LanguageFallbackChain( [
			LanguageWithConversion::factory( 'de-ch' ),
			LanguageWithConversion::factory( 'de' ),
			LanguageWithConversion::factory( 'en' ),
		] );

		return new LinkBeginHookHandler(
			$this->getEntityIdLookup(),
			new ItemIdParser(),
			$this->getTermLookup(),
			$this->getEntityNamespaceLookup(),
			$languageFallback,
			Language::factory( 'en' ),
			MediaWikiServices::getInstance()->getLinkRenderer(),
			$this->getInterwikiLookup()
		);
	}

}
