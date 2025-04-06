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

	// The following constants should be extracted to a common location (such as a static const
	// class or a config file) or kept in sync with the actual limit in the InLabelSearch class.
	private const DEFAULT_RESULTS_LIMIT = 10;
	private const DEFAULT_OFFSET = 0;

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
		int $limit = self::DEFAULT_RESULTS_LIMIT,
		int $offset = self::DEFAULT_OFFSET
	): void {
		$searchTerm = 'potato';
		$language = 'en';

		$inLabelSearch = $this->createMock( InLabelSearch::class );
		$inLabelSearch->expects( $this->once() )
			->method( 'search' )
			->with( $searchTerm, $language, Item::ENTITY_TYPE, $limit, $offset )
			->willReturn( array_slice( $results, $offset, $limit ) );

		if ( $limit == self::DEFAULT_RESULTS_LIMIT && $offset == self::DEFAULT_OFFSET ) {
			$this->assertEquals(
				$expected,
				( new InLabelSearchEngine( $inLabelSearch ) )->searchItemByLabel( $searchTerm, $language )
			);
		} else {
			$this->assertEquals(
				$expected,
				( new InLabelSearchEngine( $inLabelSearch ) )->searchItemByLabel( $searchTerm, $language, $limit, $offset )
			);
		}
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
				)
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
			range( 1, self::DEFAULT_RESULTS_LIMIT + 1 )
		);

		yield 'limit defaults to ' . self::DEFAULT_RESULTS_LIMIT => [
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
					array_slice( $potatoList, 0, self::DEFAULT_RESULTS_LIMIT )
				)
			),
		];

		yield 'limits results to provided number' => [
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
			5, // limit to pass to search method
		];

		yield 'offsets results by provided number' => [
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
			5, // limit to pass to search method
			5, // offset to pass to search method
		];
	}

	/**
	 * @dataProvider propertySearchResultsProvider
	 */
	public function testPropertySearch( array $results, PropertySearchResults $expected, int $limit = self::DEFAULT_RESULTS_LIMIT ): void {
		$searchTerm = 'some search term';
		$language = 'en';

		$inLabelSearch = $this->createMock( InLabelSearch::class );
		$inLabelSearch->expects( $this->once() )
			->method( 'search' )
			->with( $searchTerm, $language, Property::ENTITY_TYPE, $limit )
			->willReturn( array_slice( $results, 0, $limit ) );

		if ( $limit == self::DEFAULT_RESULTS_LIMIT ) {
			$this->assertEquals(
				$expected,
				( new InLabelSearchEngine( $inLabelSearch ) )->searchPropertyByLabel( $searchTerm, $language, $limit )
			);
		} else {
			$this->assertEquals(
				$expected,
				( new InLabelSearchEngine( $inLabelSearch ) )->searchPropertyByLabel( $searchTerm, $language, $limit )
			);
		}
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
			range( 1, self::DEFAULT_RESULTS_LIMIT + 1 )
		);

		yield 'limit defaults to ' . self::DEFAULT_RESULTS_LIMIT => [
			array_map( fn( array $property ) => new TermSearchResult(
				new Term( 'en', $property['label'] ),
				'label',
				new NumericPropertyId( $property['id'] ),
				new Term( 'en', $property['label'] ),
				new Term( 'en', $property['description'] )
			), $propertyList ),
			new PropertySearchResults(
				...array_map( fn( array $property ) => new PropertySearchResult(
					new NumericPropertyId( $property['id'] ),
					new Label( 'en', $property['label'] ),
					new Description( 'en', $property['description'] ),
					new MatchedData( 'label', 'en', $property['label'] )
				), array_slice( $propertyList, 0, self::DEFAULT_RESULTS_LIMIT ) )
			),
		];

		yield 'limits results to provided number' => [
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
			5, // limit to pass to search method
		];
	}

}
