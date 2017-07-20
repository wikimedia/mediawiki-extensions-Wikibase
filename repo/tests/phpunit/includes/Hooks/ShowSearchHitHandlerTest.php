<?php

namespace Wikibase\Repo\Tests\Hooks;

use Language;
use MediaWikiTestCase;
use RawMessage;
use SearchResult;
use SpecialSearch;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LanguageFallbackChain;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Hooks\ShowSearchHitHandler;
use Wikibase\Store\EntityIdLookup;

/**
 * @covers Wikibase\Repo\Hooks\ShowSearchHitHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Matěj Suchánek
 */
class ShowSearchHitHandlerTest extends MediaWikiTestCase {

	const NON_ENTITY_TITLE = 'foo';
	const DE_DESCRIPTION_ITEM_ID = 'Q1';
	const FALLBACK_DESCRIPTION_ITEM_ID = 'Q2';
	const NO_FALLBACK_DESCRIPTION_ITEM_ID = 'Q3';
	const EMPTY_ITEM_ID = 'Q4';

	/**
	 * @return SpecialSearch
	 */
	private function getSearchPage() {
		$searchPage = $this->getMockBuilder( SpecialSearch::class )
			->disableOriginalConstructor()
			->getMock();
		$searchPage->method( 'msg' )
			->willReturn( new RawMessage( ': ' ) );
		$searchPage->method( 'getLanguage' )
			->willReturn( Language::factory( 'de' ) );

		return $searchPage;
	}

	/**
	 * @param string $title
	 *
	 * @return SearchResult
	 */
	private function getSearchResult( $title ) {
		$mockTitle = $this->getMock( Title::class );
		$mockTitle->method( 'getText' )
			->willReturn( $title );
		// hack: content model equals title/id
		$mockTitle->method( 'getContentModel' )
			->willReturn( $title );

		$searchResult = $this->getMockBuilder( SearchResult::class )
			->disableOriginalConstructor()
			->getMock();
		$searchResult->method( 'getTitle' )
			->willReturn( $mockTitle );

		return $searchResult;
	}

	/**
	 * @return LanguageFallbackChain
	 */
	private function getLanguageFallbackChain() {
		$languageFallbackChain = $this->getMockBuilder( LanguageFallbackChain::class )
			->disableOriginalConstructor()
			->getMock();
		$languageFallbackChain->method( 'extractPreferredValue' )
			->willReturnCallback( function ( array $terms ) {
				if ( isset( $terms['de'] ) ) {
					return [ 'value' => $terms['de'], 'language' => 'de' ];
				} elseif ( isset( $terms['en'] ) ) {
					return [ 'value' => $terms['en'], 'language' => 'en' ];
				}
				return null;
			} );

		return $languageFallbackChain;
	}

	/**
	 * @return EntityContentFactory
	 */
	private function getEntityContentFactory() {
		$entityContentFactory = $this->getMockBuilder( EntityContentFactory::class )
			->disableOriginalConstructor()
			->getMock();
		$entityContentFactory->method( 'isEntityContentModel' )
			->willReturnCallback( function ( $contentModel ) {
				// hack: content model equals title/id
				return $contentModel !== self::NON_ENTITY_TITLE;
			} );

		return $entityContentFactory;
	}

	/**
	 * @return EntityIdLookup
	 */
	private function getEntityIdLookup() {
		$entityIdLookup = $this->getMock( EntityIdLookup::class );
		$entityIdLookup->method( 'getEntityIdForTitle' )
			->willReturnCallback( function ( Title $title ) {
				return new ItemId( $title->getText() );
			} );

		return $entityIdLookup;
	}

	/**
	 * @param ItemId $itemId
	 *
	 * @return string[]
	 */
	private function getDescriptionsArray( ItemId $itemId ) {
		switch ( $itemId->getSerialization() ) {
			case self::DE_DESCRIPTION_ITEM_ID:
				return [
					'de' => '<b>German description</b>',
					'en' => 'fallback description',
				];

			case self::FALLBACK_DESCRIPTION_ITEM_ID:
				return [
					'en' => 'fallback description',
					'fr' => 'unused description',
				];

			default:
				return [
					'fr' => 'unused description',
				];
		}
	}

	/**
	 * @param ItemId $itemId
	 *
	 * @return Item $item
	 */
	private function getEntity( ItemId $itemId ) {
		$termList = $this->getMockBuilder( TermList::class )
			->disableOriginalConstructor()
			->getMock();
		$termList->method( 'toTextArray' )
			->willReturn( $this->getDescriptionsArray( $itemId ) );

		$item = $this->getMockBuilder( Item::class )
			->disableOriginalConstructor()
			->getMock();
		$item->method( 'getDescriptions' )
			->willReturn( $termList );

		return $item;
	}

	/**
	 * @return EntityLookup
	 */
	private function getEntityLookup() {
		$entityLookup = $this->getMock( EntityLookup::class );
		$entityLookup->method( 'getEntity' )
			->willReturnCallback( function ( ItemId $itemId ) {
				return $this->getEntity( $itemId );
			} );

		return $entityLookup;
	}

	/**
	 * @dataProvider showSearchHitProvider
	 */
	public function testShowSearchHit( $title, $expected, $newExtract ) {
		$searchPage = $this->getSearchPage();
		$searchResult = $this->getSearchResult( $title );
		$handler = new ShowSearchHitHandler(
			$this->getEntityContentFactory(),
			$this->getLanguageFallbackChain(),
			$this->getEntityIdLookup(),
			$this->getEntityLookup()
		);
		$link = '<a>link</a>';
		$extract = '<span>extract</span>';
		$redirect = $section = $score = $size = $date = $related = $html = '';
		$handler->doShowSearchHit(
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
		$this->assertEquals( $expected, $link );
		$this->assertEquals( $newExtract, $extract );
	}

	public function showSearchHitProvider() {
		return [
			'non-entity' => [
				self::NON_ENTITY_TITLE,
				'<a>link</a>',
				'<span>extract</span>',
			],
			'German description' => [
				self::DE_DESCRIPTION_ITEM_ID,
				'<a>link</a>: <span class="wb-itemlink-description">&lt;b>German description&lt;/b></span>',
				'',
			],
			'fallback description' => [
				self::FALLBACK_DESCRIPTION_ITEM_ID,
				'<a>link</a>: <span class="wb-itemlink-description" dir="auto" lang="en">fallback description</span>',
				'',
			],
			'no available fallback description' => [
				self::NO_FALLBACK_DESCRIPTION_ITEM_ID,
				'<a>link</a>',
				'',
			],
			'description-less item' => [
				self::EMPTY_ITEM_ID,
				'<a>link</a>',
				'',
			],
		];
	}

}
