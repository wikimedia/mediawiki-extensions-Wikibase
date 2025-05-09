<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Infrastructure\DataAccess;

use Generator;
use MediaWiki\Registration\ExtensionRegistry;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\Description;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Model\Label;
use Wikibase\Repo\Domains\Search\Domain\Model\MatchedData;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResults;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\InLabelSearchEngine;
use Wikibase\Search\Elastic\InLabelSearch;

/**
 * @covers \Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\InLabelSearchEngine
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class InLabelSearchEngineTest extends TestCase {

	public static function setUpBeforeClass(): void {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'WikibaseCirrusSearch' ) ) {
			self::markTestSkipped( 'CirrusSearch needs to be enabled to run this test' );
		}
	}

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

		$inLabelSearch = $this->createMock( InLabelSearch::class );
		$inLabelSearch->expects( $this->once() )
			->method( 'search' )
			->with( $searchTerm, $language, Item::ENTITY_TYPE, $limit, $offset )
			->willReturn( array_slice( $results, $offset, $limit ) );

			$this->assertEquals(
				$expected,
				( new InLabelSearchEngine( $inLabelSearch ) )->searchItemByLabel( $searchTerm, $language, $limit, $offset )
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

	/**
	 * @dataProvider propertySearchResultsProvider
	 */
	public function testPropertySearch(
		array $results,
		PropertySearchResults $expected,
		int $limit = 10,
		int $offset = 0
	): void {
		$searchTerm = 'some search term';
		$language = 'en';

		$inLabelSearch = $this->createMock( InLabelSearch::class );
		$inLabelSearch->expects( $this->once() )
			->method( 'search' )
			->with( $searchTerm, $language, Property::ENTITY_TYPE, $limit, $offset )
			->willReturn( array_slice( $results, $offset, $limit ) );

			$this->assertEquals(
				$expected,
				( new InLabelSearchEngine( $inLabelSearch ) )->searchPropertyByLabel( $searchTerm, $language, $limit, $offset )
			);
	}

	public static function propertySearchResultsProvider(): Generator {
		yield 'no results' => [ [], new PropertySearchResults() ];
		yield 'some results' => [
			[
				new TermSearchResult(
					new Term( 'en', 'property label' ),
					'label',
					new NumericPropertyId( 'P123' ),
					new Term( 'en', 'property label' ),
					new Term( 'en', 'property description' )
				),
				new TermSearchResult(
					new Term( 'en', 'property 2 label' ),
					'label',
					new NumericPropertyId( 'P321' ),
					new Term( 'en', 'property 2 label' ),
					new Term( 'en', 'property 2 description' )
				),
			],
			new PropertySearchResults(
				new PropertySearchResult(
					new NumericPropertyId( 'P123' ),
					new Label( 'en', 'property label' ),
					new Description( 'en', 'property description' ),
					new MatchedData( 'label', 'en', 'property label' )
				),
				new PropertySearchResult(
					new NumericPropertyId( 'P321' ),
					new Label( 'en', 'property 2 label' ),
					new Description( 'en', 'property 2 description' ),
					new MatchedData( 'label', 'en', 'property 2 label' )
				)
			),
		];
		yield 'alias as display label' => [
			[
				new TermSearchResult(
					new Term( 'en', 'property alias' ),
					'alias',
					new NumericPropertyId( 'P123' ),
					new Term( 'en', 'property alias' ),
					new Term( 'en', 'property description' )
				),
			],
			new PropertySearchResults(
				new PropertySearchResult(
					new NumericPropertyId( 'P123' ),
					new Label( 'en', 'property alias' ),
					new Description( 'en', 'property description' ),
					new MatchedData( 'alias', 'en', 'property alias' )
				)
			),
		];

		$propertyList = array_map(
			fn( int $i ) => [
				'id' => "P12$i",
				'label' => "property $i",
				'description' => "property description $i",
			],
			range( 1, 11 )
		);

		yield 'pagination result with limit' => [
			array_map(
				fn( array $property ) => new TermSearchResult(
					new Term( 'en', $property['label'] ),
					'label',
					new NumericPropertyId( $property['id'] ),
					new Term( 'en', $property['label'] ),
					new Term( 'en', $property['description'] )
				),
				$propertyList
			),
			new PropertySearchResults(
				...array_map(
					fn( array $property ) => new PropertySearchResult(
						new NumericPropertyId( $property['id'] ),
						new Label( 'en', $property['label'] ),
						new Description( 'en', $property['description'] ),
						new MatchedData( 'label', 'en', $property['label'] )
					),
					array_slice( $propertyList, 0, 5 )
				)
			),
			5,
			0,
		];

		yield 'pagination result with offset and limit' => [
			array_map(
				fn( array $property ) => new TermSearchResult(
					new Term( 'en', $property['label'] ),
					'label',
					new NumericPropertyId( $property['id'] ),
					new Term( 'en', $property['label'] ),
					new Term( 'en', $property['description'] )
				),
				$propertyList
			),
			new PropertySearchResults(
				...array_map(
					fn( array $property ) => new PropertySearchResult(
						new NumericPropertyId( $property['id'] ),
						new Label( 'en', $property['label'] ),
						new Description( 'en', $property['description'] ),
						new MatchedData( 'label', 'en', $property['label'] )
					),
					array_slice( $propertyList, 5, 5 )
				)
			),
			5,
			5,
		];
	}

}
