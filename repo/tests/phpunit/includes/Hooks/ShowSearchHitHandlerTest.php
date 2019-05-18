<?php

namespace Wikibase\Repo\Tests\Hooks;

use ContextSource;
use ExtensionRegistry;
use HtmlArmor;
use Language;
use MediaWikiTestCase;
use MWException;
use RawMessage;
use SearchResult;
use SpecialSearch;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\LanguageFallbackChain;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\Hooks\ShowSearchHitHandler;
use Wikibase\Store\EntityIdLookup;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\Hooks\ShowSearchHitHandler
 *
 * @group Wikibase
 * @group NotIsolatedUnitTest
 *
 * @license GPL-2.0-or-later
 * @author Matěj Suchánek
 */
class ShowSearchHitHandlerTest extends MediaWikiTestCase {

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
		$searchPage = $this->getMockBuilder( SpecialSearch::class )
			->disableOriginalConstructor()
			->getMock();
		$searchPage->method( 'msg' )
			->willReturnCallback(
				function () {
					return new RawMessage( implode( ",", func_get_args() ) );
				}
			);
		$searchPage->method( 'getLanguage' )
			->willReturn( Language::factory( $language ) );

		$context = $this->getMockBuilder( ContextSource::class )
			->disableOriginalConstructor()
			->getMock();
		$context->method( 'getLanguage' )
			->willReturn( Language::factory( $language ) );
		$context->method( 'getUser' )
			->willReturn( MediaWikiTestCase::getTestUser()->getUser() );

		$searchPage->method( 'getContext' )->willReturn( $context );

		return $searchPage;
	}

	/**
	 * @param string $title
	 *
	 * @return SearchResult
	 */
	private function getSearchResult( $title ) {
		$mockTitle = $this->getMock( Title::class );
		$mockTitle->method( 'getText' )->willReturn( $title );
		// hack: content model equals title/id
		$mockTitle->method( 'getContentModel' )->willReturn( $title );
		$mockTitle->method( 'getPrefixedText' )->willReturn( "Prefix:$title" );
		$mockTitle->method( 'getFullText' )->willReturn( "Prefix:$title" );

		$searchResult = $this->getMockBuilder( SearchResult::class )
			->disableOriginalConstructor()
			->getMock();
		$searchResult->method( 'getTitle' )->willReturn( $mockTitle );

		return $searchResult;
	}

	public function testShowSearchHitNonEntity() {
		$searchPage = $this->getSearchPage( 'en' );
		$link = '<a>link</a>';
		$extract = '<span>extract</span>';
		$redirect = $section = $score = $size = $date = $related = $html = '';
		$searchResult = $this->getMock( SearchResult::class );
		$searchResult->method( 'getTitle' )->willReturn( Title::newFromText( 'Test', NS_TALK ) );
		ShowSearchHitHandler::onShowSearchHit(
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
	 * @param string[] $languages
	 * @param Item[] $entities
	 * @return ShowSearchHitHandler
	 */
	private function getShowSearchHitHandler( array $languages, array $entities ) {
		return new ShowSearchHitHandler(
			$this->getEntityContentFactory(),
			$this->getMockFallbackChain( $languages ),
			$this->getEntityIdLookup(),
			$this->getEntityLookup( $entities ),
			new DefaultEntityLinkFormatter( Language::factory( 'en' ) )
		);
	}

	/**
	 * @param string[] $languages
	 * @return LanguageFallbackChain
	 */
	private function getMockFallbackChain( array $languages ) {
		$mock = $this->getMockBuilder( LanguageFallbackChain::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'getFetchLanguageCodes' )
			->will( $this->returnValue( $languages ) );
		$mock->expects( $this->any() )
			->method( 'extractPreferredValue' )
			->will( $this->returnCallback( function ( $sourceData ) use ( $languages ) {
				foreach ( $languages as $language ) {
					if ( isset( $sourceData[$language] ) ) {
						return [ 'language' => $language, 'value' => $sourceData[$language] ];
					}
				}
				return null;
			} ) );
		return $mock;
	}

	/**
	 * @param Item[] $entities Map ID -> Entity
	 * @return EntityLookup
	 */
	private function getEntityLookup( array $entities ) {
		$entityLookup = $this->getMock( EntityLookup::class );
		$entityLookup->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnCallback( function ( ItemId $id ) use ( $entities ) {
				$key = $id->getSerialization();
				return $entities[$key];
			} ) );
		return $entityLookup;
	}

	/**
	 * @return EntityContentFactory
	 */
	private function getEntityContentFactory() {
		$entityContentFactory = $this->getMockBuilder( EntityContentFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$entityContentFactory->expects( $this->any() )
			->method( 'isEntityContentModel' )
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
				'en'
			],
			"de" => [
				'Q2',
				[ 'en' => 'Test 1', 'de' => 'Test DE' ],
				'de',
				[ 'de' ],
				'de'
			],
			"fallback" => [
				'Q3',
				[ 'ru' => 'Test RU', 'en' => 'Test 1' ],
				'de',
				[ 'de', 'ru', 'en' ],
				'de-ru'
			],
			"no fallback" => [
				'Q3',
				[ 'ru' => 'Test RU', 'en' => 'Test 1' ],
				'de',
				[ 'de', 'es' ],
				'de-none'
			],
			"html" => [
				'Q4',
				[ 'en' => 'Test <with> 1 & 2', 'de' => 'Test <DE>' ],
				'en',
				[ 'en' ],
				'en-html'
			],
		];
	}

	/**
	 * @dataProvider getPlainSearches
	 * @param string $title
	 * @param string[] $labels
	 * @param string $displayLanguage
	 * @param string[] $languages
	 * @param string $expected
	 * @throws MWException
	 */
	public function testShowSearchHitPlain( $title, array $labels, $displayLanguage, array $languages, $expected ) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'CLDR' ) ) {
			// This test uses language names from CLDR
			$this->markTestSkipped( 'cldr not installed, skipping' );
		}

		$testFile = __DIR__ . '/../../data/searchHits/' . $expected . ".plain.html";

		$entities[$title] = $this->makeItem( $title, $labels );
		$showHandler = TestingAccessWrapper::newFromObject(
			$this->getShowSearchHitHandler( $languages, $entities )
		);
		$searchPage = $this->getSearchPage( $displayLanguage );

		$searchResult = $this->getSearchResult( $title );

		$link = '<a>link</a>';
		$extract = '<span>extract</span>';
		$redirect = $section = $score = $size = $date = $related = $html = '';
		$title = "TITLE";

		$showHandler->__call(
			'showPlainSearchHit',
			[
				$searchPage,
				$searchResult,
				[],
				&$link,
				&$redirect,
				&$section,
				&$extract,
				&$score,
				&$size,
				&$date,
				&$related,
				&$html,
			]
		);

		$showHandler->__call(
			'showPlainSearchTitle',
			[
				$searchResult->getTitle(),
				&$title,
			]
		);

		$output = HtmlArmor::getHtml( $title ) . "\n" .
				$extract . "\n" .
				$size;

		$this->assertFileContains( $testFile, $output );
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
