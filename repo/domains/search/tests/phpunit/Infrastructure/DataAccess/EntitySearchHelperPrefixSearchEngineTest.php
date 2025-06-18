<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Infrastructure\DataAccess;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\Domains\Search\Domain\Model\Description;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Model\Label;
use Wikibase\Repo\Domains\Search\Domain\Model\MatchedData;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\EntitySearchHelperPrefixSearchEngine;

/**
 * @covers \Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\EntitySearchHelperPrefixSearchEngine
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySearchHelperPrefixSearchEngineTest extends TestCase {

	/**
	 * @dataProvider itemSearchResultsProvider
	 */
	public function testItemSearch(
		array $results,
		ItemSearchResults $expected,
		int $limit = 10,
		int $offset = 0
	): void {
		$searchTerm = 'potato';
		$language = 'en';

		$entitySearchHelper = $this->createMock( EntitySearchHelper::class );
		$entitySearchHelper->expects( $this->once() )
			->method( 'getRankedSearchResults' )
			->with( $searchTerm, $language, Item::ENTITY_TYPE, $limit + $offset + 1, false, null )
			->willReturn( $results );

			$this->assertEquals(
				$expected,
				( new EntitySearchHelperPrefixSearchEngine( $entitySearchHelper ) )->suggestItems( $searchTerm, $language, $limit, $offset )
			);
	}

	public static function itemSearchResultsProvider(): Generator {
		yield 'no results' => [ [], new ItemSearchResults() ];
		yield 'some results' => [
			[
				new TermSearchResult(
					new Term( 'en', 'potato' ),
					'label',
					new ItemId( 'Q123' ),
					new Term( 'en', 'potato' ),
					new Term( 'en', 'root vegetable' )
				),
				new TermSearchResult(
					new Term( 'en', 'sweet potato' ),
					'label',
					new ItemId( 'Q321' ),
					new Term( 'en', 'sweet potato' ),
					new Term( 'en', 'sweet root vegetable' )
				),
			],
			new ItemSearchResults(
				new ItemSearchResult(
					new ItemId( 'Q123' ),
					new Label( 'en', 'potato' ),
					new Description( 'en', 'root vegetable' ),
					new MatchedData( 'label', 'en', 'potato' )
				),
				new ItemSearchResult(
					new ItemId( 'Q321' ),
					new Label( 'en', 'sweet potato' ),
					new Description( 'en', 'sweet root vegetable' ),
					new MatchedData( 'label', 'en', 'sweet potato' )
				),
			),
		];
		yield 'alias as display label' => [
			[
				new TermSearchResult(
					new Term( 'en', 'spud' ),
					'alias',
					new ItemId( 'Q123' ),
					new Term( 'en', 'spud' ),
					new Term( 'en', 'root vegetable' )
				),
			],
			new ItemSearchResults(
				new ItemSearchResult(
					new ItemId( 'Q123' ),
					new Label( 'en', 'spud' ),
					new Description( 'en', 'root vegetable' ),
					new MatchedData( 'alias', 'en', 'spud' )
				)
			),
		];

		$potatoList = array_map(
			fn( int $i ) => [
				'id' => "Q12$i",
				'label' => "potato $i",
				'description' => "root vegetable $i",
			],
			range( 1, 11 )
		);

		yield 'pagination result with limit' => [
			array_map(
				fn( array $potato ) => new TermSearchResult(
					new Term( 'en', $potato['label'] ),
					'label',
					new ItemId( $potato['id'] ),
					new Term( 'en', $potato['label'] ),
					new Term( 'en', $potato['description'] )
				),
				$potatoList
			),
			new ItemSearchResults(
				...array_map(
					fn( array $potato ) => new ItemSearchResult(
						new ItemId( $potato['id'] ),
						new Label( 'en', $potato['label'] ),
						new Description( 'en', $potato['description'] ),
						new MatchedData( 'label', 'en', $potato['label'] )
					),
					array_slice( $potatoList, 0, 5 )
				)
			),
			5,
			0,
		];

		yield 'pagination result with limit and offset' => [
			array_map(
				fn( array $potato ) => new TermSearchResult(
					new Term( 'en', $potato['label'] ),
					'label',
					new ItemId( $potato['id'] ),
					new Term( 'en', $potato['label'] ),
					new Term( 'en', $potato['description'] )
				),
				$potatoList
			),
			new ItemSearchResults(
				...array_map(
					fn( array $potato ) => new ItemSearchResult(
						new ItemId( $potato['id'] ),
						new Label( 'en', $potato['label'] ),
						new Description( 'en', $potato['description'] ),
						new MatchedData( 'label', 'en', $potato['label'] )
					),
					array_slice( $potatoList, 5, 5 )
				)
			),
			5,
			5,
		];
	}

}
