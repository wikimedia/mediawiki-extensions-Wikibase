<?php

namespace Wikibase\Repo\Tests\Hooks;

use Language;
use MediaWikiTestCase;
use Message;
use SearchResult;
use SpecialSearch;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Store\EntityTitleLookup;
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
			->with( $this->equalTo( 'colon-separator' ) )
			->will( $this->returnCallback( function ( $key ) {
				$msg = $this->getMockBuilder( Message::class )
					->disableOriginalConstructor()
					->getMock();
				$msg->method( 'escaped' )->willReturn( ': ' );

				return $msg;
			} ) );
		$searchPage->method( 'getLanguage' )
			->willReturn( Language::factory( 'de' ) );

		return $searchPage;
	}

	/**
	 * @param string $title
	 * @return SearchResult
	 */
	private function getSearchResult( $title ) {
		$searchResult = $this->getMockBuilder( SearchResult::class )
			->disableOriginalConstructor()
			->getMock();
		$searchResult->method( 'getTitle' )
			->will( $this->returnCallback( function () use ( $title ) {
				$mock = $this->getMock( Title::class );
				// hack: content model equals title/id
				$mock->method( 'getContentModel' )
					->will( $this->returnValue( $title ) );

				return $mock;
			} ) );

		return $searchResult;
	}

	/**
	 * @see LanguageFallbackChain::extractPreferredValue
	 * @param array $terms
	 * @return array|null
	 */
	private function extractPreferredValue( array $terms ) {
		if ( array_key_exists( 'de', $terms ) ) {
			return [
				'value' => $terms['de'],
				'language' => 'de',
			];
		} elseif ( array_key_exists( 'en', $terms ) ) {
			return [
				'value' => $terms['en'],
				'language' => 'en',
			];
		}

		return null;
	}

	/**
	 * @return LanguageFallbackChain
	 */
	private function getLanguageFallbackChain() {
		$languageFallbackChain = $this->getMockBuilder( LanguageFallbackChain::class )
			->disableOriginalConstructor()
			->getMock();
		$languageFallbackChain->method( 'extractPreferredValue' )
			->will( $this->returnCallback( function ( array $terms ) {
				return $this->extractPreferredValue( $terms );
			} ) );

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
			->will( $this->returnCallback( function ( $contentModel ) {
				// hack: content model equals title/id
				return $contentModel !== self::NON_ENTITY_TITLE;
			} ) );

		return $entityContentFactory;
	}

	/**
	 * @return EntityIdLookup
	 */
	private function getEntityIdLookup() {
		$entityIdLookup = $this->getMock( EntityIdLookup::class );
		$entityIdLookup->method( 'getEntityIdForTitle' )
			->will( $this->returnCallback( function ( Title $title ) {
				// hack: content model equals title/id
				return new ItemId( $title->getContentModel() );
			} ) );

		return $entityIdLookup;
	}

	/**
	 * @param ItemId $itemId
	 * @return string[]
	 */
	private function getDescriptionsArray( ItemId $itemId ) {
		$terms = [];
		switch ( $itemId->getSerialization() ) {
			case self::DE_DESCRIPTION_ITEM_ID:
				$terms['de'] = '<b>German description</b>';
			case self::FALLBACK_DESCRIPTION_ITEM_ID:
				$terms['en'] = 'fallback description';
			case self::NO_FALLBACK_DESCRIPTION_ITEM_ID:
				$terms['fr'] = 'unused description';
			case self::EMPTY_ITEM_ID:
				break;
		}
		return $terms;
	}

	/**
	 * @param ItemId $itemId
	 * @return Item $item
	 */
	private function getEntity( ItemId $itemId ) {
		$item = $this->getMockBuilder( Item::class )
			->disableOriginalConstructor()
			->getMock();
		$item->method( 'getDescriptions' )
			->will( $this->returnCallback( function() use ( $itemId ) {
				$termList = $this->getMockBuilder( TermList::class )
					->disableOriginalConstructor()
					->getMock();
				$termList->method( 'toTextArray' )
					->will( $this->returnCallback( function () use ( $itemId ) {
						return $this->getDescriptionsArray( $itemId );
					} ) );

				return $termList;
			} ) );

		return $item;
	}

	/**
	 * @return EntityLookup
	 */
	private function getEntityLookup() {
		$entityLookup = $this->getMock( EntityLookup::class );
		$entityLookup->method( 'getEntity' )
			->will( $this->returnCallback( function ( ItemId $itemId ) {
				return $this->getEntity( $itemId );
			} ) );

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
