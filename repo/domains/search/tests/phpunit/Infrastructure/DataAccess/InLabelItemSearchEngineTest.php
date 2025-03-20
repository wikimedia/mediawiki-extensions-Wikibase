<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Infrastructure\DataAccess;

use Generator;
use MediaWiki\Registration\ExtensionRegistry;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\Description;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Model\Label;
use Wikibase\Repo\Domains\Search\Domain\Model\MatchedData;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\InLabelItemSearchEngine;
use Wikibase\Search\Elastic\InLabelSearch;

/**
 * @covers \Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\InLabelItemSearchEngine
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class InLabelItemSearchEngineTest extends TestCase {
	public static function setUpBeforeClass(): void {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'WikibaseCirrusSearch' ) ) {
			self::markTestSkipped( 'CirrusSearch needs to be enabled to run this test' );
		}
	}

	/**
	 * @dataProvider searchResultsProvider
	 */
	public function testSearch( array $results, ItemSearchResults $expected ): void {
		$searchTerm = 'potato';
		$language = 'en';

		$inLabelSearch = $this->createMock( InLabelSearch::class );
		$inLabelSearch->expects( $this->once() )
			->method( 'search' )
			->with( $searchTerm, $language, Item::ENTITY_TYPE, 5 )
			->willReturn( $results );

		$this->assertEquals(
			$expected,
			( new InLabelItemSearchEngine( $inLabelSearch ) )->searchItemByLabel( $searchTerm, $language )
		);
	}

	public static function searchResultsProvider(): Generator {
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
	}

}
