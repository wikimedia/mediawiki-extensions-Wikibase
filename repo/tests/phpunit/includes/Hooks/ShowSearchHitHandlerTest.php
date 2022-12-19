<?php

namespace Wikibase\Repo\Tests\Hooks;

use ContextSource;
use HtmlArmor;
use MediaWikiIntegrationTestCase;
use MWException;
use RawMessage;
use SearchResult;
use SpecialSearch;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Hooks\ShowSearchHitHandler;

/**
 * @covers \Wikibase\Repo\Hooks\ShowSearchHitHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Matěj Suchánek
 */
class ShowSearchHitHandlerTest extends MediaWikiIntegrationTestCase {

	/**
	 * Test cases that should be covered:
	 * - non-Entity result
	 * - name+description
	 * - name missing
	 * - description missing
	 * - both missing
	 * - name+description with extra data
	 * - name + description in different language
	 * - name + description + extra data in different language
	 */

	/**
	 * @param string $language
	 * @return SpecialSearch
	 * @throws MWException
	 */
	private function getSearchPage( $language ) {
		$searchPage = $this->createMock( SpecialSearch::class );
		$searchPage->method( 'msg' )
			->willReturnCallback(
				function ( ...$args ) {
					return new RawMessage( implode( ",", $args ) );
				}
			);
		$lang = $this->getServiceContainer()->getLanguageFactory()->getLanguage( $language );
		$searchPage->method( 'getLanguage' )
			->willReturn( $lang );

		$context = $this->createMock( ContextSource::class );
		$context->method( 'getLanguage' )
			->willReturn( $lang );
		$context->method( 'getUser' )
			->willReturn( MediaWikiIntegrationTestCase::getTestUser()->getUser() );

		$searchPage->method( 'getContext' )->willReturn( $context );

		return $searchPage;
	}

	/**
	 * @param string $title
	 *
	 * @return SearchResult
	 */
	private function getSearchResult( $title ) {
		$mockTitle = $this->createMock( Title::class );
		$mockTitle->method( 'getText' )->willReturn( $title );
		// hack: content model equals title/id
		$mockTitle->method( 'getContentModel' )->willReturn( $title );
		$mockTitle->method( 'getPrefixedText' )->willReturn( "Prefix:$title" );
		$mockTitle->method( 'getFullText' )->willReturn( "Prefix:$title" );

		$searchResult = $this->createMock( SearchResult::class );
		$searchResult->method( 'getTitle' )->willReturn( $mockTitle );

		return $searchResult;
	}

	public function testShowSearchHitNonEntity() {
		$searchPage = $this->getSearchPage( 'en' );
		$link = '<a>link</a>';
		$extract = '<span>extract</span>';
		$redirect = $section = $score = $size = $date = $related = $html = '';
		$searchResult = $this->createMock( SearchResult::class );
		$searchResult->method( 'getTitle' )->willReturn( Title::makeTitle( NS_TALK, 'Test' ) );
		$handler = $this->getShowSearchHitHandler();
		$handler->onShowSearchHit(
			$searchPage,
			$searchResult,
			[],
			$link,
			$redirect,
			$section,
			$extract,
			$score,
			$size,
			$date,
			$related,
			$html
		);
		$this->assertEquals( '<a>link</a>', $link );
		$this->assertEquals( '<span>extract</span>', $extract );
	}

	/**
	 * @return EntityIdLookup
	 */
	private function getEntityIdLookup() {
		$entityIdLookup = $this->createMock( EntityIdLookup::class );

		$entityIdLookup->method( 'getEntityIdForTitle' )
			->willReturnCallback( function( Title $title ) {
				if ( preg_match( '/^Q(\d+)$/', $title->getText(), $m ) ) {
					return new ItemId( $m[0] );
				}

				return null;
			} );

		return $entityIdLookup;
	}

	/**
	 * @param Item[] $entities
	 * @param EntityLookup|null $lookup
	 *
	 * @param TermLanguageFallbackChain|null $fallbackChain
	 * @return ShowSearchHitHandler
	 */
	private function getShowSearchHitHandler(
		array $entities = [],
		EntityLookup $lookup = null,
		TermLanguageFallbackChain $fallbackChain = null
	) {
		return new ShowSearchHitHandler(
			$this->getEntityContentFactory(),
			$this->getEntityIdLookup(),
			$lookup ?? $this->getEntityLookup( $entities ),
			$this->getFallbackChainFactory( $fallbackChain )
		);
	}

	private function getFallbackChainFactory( TermLanguageFallbackChain $mockChain = null ): LanguageFallbackChainFactory {
		$factory = $this->createMock( LanguageFallbackChainFactory::class );
		if ( $mockChain !== null ) {
			$factory->method( 'newFromContext' )->willReturn( $mockChain );
		}
		return $factory;
	}

	/**
	 * @param string[] $languages
	 * @return TermLanguageFallbackChain
	 */
	private function getMockFallbackChain( array $languages ) {
		$mock = $this->createMock( TermLanguageFallbackChain::class );
		$mock->method( 'getFetchLanguageCodes' )
			->willReturn( $languages );
		$mock->method( 'extractPreferredValue' )
			->willReturnCallback( function ( $sourceData ) use ( $languages ) {
				foreach ( $languages as $language ) {
					if ( isset( $sourceData[$language] ) ) {
						return [ 'language' => $language, 'value' => $sourceData[$language] ];
					}
				}
				return null;
			} );
		return $mock;
	}

	/**
	 * @param Item[] $entities Map ID -> Entity
	 * @return EntityLookup
	 */
	private function getEntityLookup( array $entities = null ) {
		$entityLookup = $this->createMock( EntityLookup::class );
		if ( isset( $entities ) ) {
			$entityLookup->method( 'getEntity' )
				->willReturnCallback( function ( ItemId $id ) use ( $entities ) {
					$key = $id->getSerialization();
					return $entities[$key];
				} );
		}
		return $entityLookup;
	}

	/**
	 * @return EntityContentFactory
	 */
	private function getEntityContentFactory() {
		$entityContentFactory = $this->createMock( EntityContentFactory::class );

		$entityContentFactory->method( 'isEntityContentModel' )
			->willReturn( true );

		return $entityContentFactory;
	}

	public function getPlainSearches() {
		return [
			"simple" => [
				'Q1',
				[ 'en' => 'Test 1', 'de' => 'Test DE' ],
				'en',
				[ 'en' ],
				'en',
			],
			"de" => [
				'Q2',
				[ 'en' => 'Test 1', 'de' => 'Test DE' ],
				'de',
				[ 'de' ],
				'de',
			],
			"fallback" => [
				'Q3',
				[ 'ru' => 'Test RU', 'en' => 'Test 1' ],
				'de',
				[ 'de', 'ru', 'en' ],
				'de-ru',
			],
			"no fallback" => [
				'Q3',
				[ 'ru' => 'Test RU', 'en' => 'Test 1' ],
				'de',
				[ 'de', 'es' ],
				'de-none',
			],
			"html" => [
				'Q4',
				[ 'en' => 'Test <with> 1 & 2', 'de' => 'Test <DE>' ],
				'en',
				[ 'en' ],
				'en-html',
			],
		];
	}

	/**
	 * @dataProvider getPlainSearches
	 */
	public function testShowSearchHitPlain(
		string $title,
		array $labels,
		string $displayLanguage,
		array $languages,
		string $expected
	) {
		$this->markTestSkippedIfExtensionNotLoaded( 'CLDR' );

		$testFile = __DIR__ . '/../../data/searchHits/' . $expected . ".plain.html";

		$entities[$title] = $this->makeItem( $title, $labels );

		$showHandler = $this->getShowSearchHitHandler( $entities, null, $this->getMockFallbackChain( $languages ) );
		$searchPage = $this->getSearchPage( $displayLanguage );

		$searchResult = $this->getSearchResult( $title );

		$link = '<a>link</a>';
		$extract = '<span>extract</span>';
		$redirect = $section = $score = $size = $date = $related = $html = '';
		$title = "TITLE";

		$showHandler->onShowSearchHit(
			$searchPage,
			$searchResult,
			[],
			$link,
			$redirect,
			$section,
			$extract,
			$score,
			$size,
			$date,
			$related,
			$html
		);

		$searchTitle = $searchResult->getTitle();
		$showHandler->onShowSearchHitTitle(
			$searchTitle,
			$title,
			$searchResult,
			[],
			$searchPage,
			$query,
			$attributes
		);

		$output = HtmlArmor::getHtml( $title ) . "\n" .
				$extract . "\n" .
				$size;

		$this->assertFileContains( $testFile, $output );
	}

	/**
	 * Searches of items with Double Redirects result in UnresolvedEntityRedirectException
	 * being thrown by the EntityLookup, this should be handled and not bubble up to the ui.
	 *
	 * @see https://phabricator.wikimedia.org/T251880
	 */
	public function testRedirectExceptionsShouldReturnNothing() {
		$displayLanguage = 'en';

		$lookup = $this->getEntityLookup();
		$lookup->method( 'getEntity' )
			->willThrowException( new UnresolvedEntityRedirectException( new ItemId( 'Q1' ), new ItemId( 'Q2' ) ) );
		$showHandler = $this->getShowSearchHitHandler( [], $lookup );

		$link = '<a>link</a>';
		$extract = '<span>unaltered extract</span>';
		$redirect = $section = $score = $size = $date = $related = $html = '';

		$showHandler->onShowSearchHit(
				$this->getSearchPage( $displayLanguage ),
				$this->getSearchResult( "TITLE" ),
				[],
				$link,
				$redirect,
				$section,
				$extract,
				$score,
				$size,
				$date,
				$related,
				$html
		);
		$this->assertSame( '<span>unaltered extract</span>', $extract );
		$this->assertSame( '', $size );
	}

	/**
	 * @param string $id
	 * @param string[] $labels
	 * @return Item
	 */
	protected function makeItem( $id, array $labels ) {
		$item = new Item( new ItemId( $id ) );
		foreach ( $labels as $l => $v ) {
			$item->setLabel( $l, $v );
			$item->setDescription( $l, "Desc: $v" );
		}
		$item->getSiteLinkList()->addSiteLink( new SiteLink( 'enwiki', 'Main_Page' ) );
		$item->getStatements()->addNewStatement( new PropertyNoValueSnak( 1 ) );
		$item->getStatements()->addNewStatement( new PropertyNoValueSnak( 2 ) );
		return $item;
	}

}
