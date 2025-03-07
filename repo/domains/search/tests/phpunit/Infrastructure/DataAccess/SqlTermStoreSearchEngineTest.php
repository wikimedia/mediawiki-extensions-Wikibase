<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Infrastructure\DataAccess;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\Store\MatchingTermsLookup;
use Wikibase\Lib\TermIndexEntry;
use Wikibase\Repo\Domains\Search\Domain\Model\Description;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Model\Label;
use Wikibase\Repo\Domains\Search\Domain\Model\MatchedData;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\SqlTermStoreSearchEngine;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\TermRetriever;

/**
 * @covers \Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\SqlTermStoreSearchEngine
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SqlTermStoreSearchEngineTest extends TestCase {

	private MatchingTermsLookup $matchingTermsLookup;
	private TermRetriever $termRetriever;

	protected function setUp(): void {
		parent::setUp();
		$this->matchingTermsLookup = $this->createStub( MatchingTermsLookup::class );
		$this->termRetriever = $this->createStub( TermRetriever::class );
	}

	public function testGivenSearchResultForLabel(): void {
		$searchTerm = 'potato';
		$languageCode = 'en';

		$expectedSearchResult = new ItemSearchResult(
			new ItemId( 'Q123' ),
			new Label( 'en', 'potato' ),
			new Description( 'en', 'staple food' ),
			new MatchedData( TermTypes::TYPE_LABEL, 'en', 'potato' )
		);

		$this->matchingTermsLookup = $this->createMock( MatchingTermsLookup::class );
		$this->matchingTermsLookup->expects( $this->exactly( 2 ) )
			->method( 'getMatchingTerms' )
			->willReturnOnConsecutiveCalls(
				[
					new TermIndexEntry( [ // one entry with matching label will be found
						TermIndexEntry::FIELD_TYPE => TermTypes::TYPE_LABEL,
						TermIndexEntry::FIELD_LANGUAGE => 'en',
						TermIndexEntry::FIELD_TEXT => 'potato',
						TermIndexEntry::FIELD_ENTITY => new ItemId( 'Q123' ),
					] ),
				],
				[], // no entry with matching alias will be found
			);

		$this->termRetriever = $this->createMock( TermRetriever::class );
		// we will not call TermRetriever::getLabel() because the index entry is of type label already
		$this->termRetriever->expects( $this->never() )
			->method( 'getLabel' );
		$this->termRetriever->expects( $this->once() )
			->method( 'getDescription' )
			->with( 'Q123', $languageCode )
			->willReturn( new Description( 'en', 'staple food' ) );

		$this->assertEquals(
			new ItemSearchResults( $expectedSearchResult ),
			$this->newEngine()->searchItemByLabel( $searchTerm, $languageCode )
		);
	}

	public function testGivenSearchResultForAlias(): void {
		$searchTerm = 'spud';
		$languageCode = 'en';

		$expectedSearchResult = new ItemSearchResult(
			new ItemId( 'Q123' ),
			new Label( 'en', 'potato' ),
			new Description( 'en', 'staple food' ),
			new MatchedData( TermTypes::TYPE_ALIAS, 'en', 'spud' )
		);

		$this->matchingTermsLookup = $this->createMock( MatchingTermsLookup::class );
		$this->matchingTermsLookup->expects( $this->exactly( 2 ) )
			->method( 'getMatchingTerms' )
			->willReturnOnConsecutiveCalls(
				[], // no entry with matching label will be found
				[
					new TermIndexEntry( [ // one entry with matching alias will be found
						TermIndexEntry::FIELD_TYPE => TermTypes::TYPE_ALIAS,
						TermIndexEntry::FIELD_LANGUAGE => 'en',
						TermIndexEntry::FIELD_TEXT => 'spud',
						TermIndexEntry::FIELD_ENTITY => new ItemId( 'Q123' ),
					] ),
				],
			);

		$this->termRetriever = $this->createMock( TermRetriever::class );
		$this->termRetriever->expects( $this->once() )
			->method( 'getLabel' )
			->with( 'Q123', $languageCode )
			->willReturn( new Label( 'en', 'potato' ) );
		$this->termRetriever->expects( $this->once() )
			->method( 'getDescription' )
			->with( 'Q123', $languageCode )
			->willReturn( new Description( 'en', 'staple food' ) );

		$this->assertEquals(
			new ItemSearchResults( $expectedSearchResult ),
			$this->newEngine()->searchItemByLabel( $searchTerm, $languageCode )
		);
	}

	public function testGivenSearchFails_returnsNoResults(): void {
		$searchTerm = 'potato';
		$languageCode = 'en';

		$this->matchingTermsLookup = $this->createMock( MatchingTermsLookup::class );
		$this->matchingTermsLookup->expects( $this->exactly( 2 ) )
			->method( 'getMatchingTerms' )
			->willReturn( [] );

		$this->termRetriever = $this->createMock( TermRetriever::class );
		$this->termRetriever->expects( $this->never() )
			->method( 'getLabel' );
		$this->termRetriever->expects( $this->never() )
			->method( 'getDescription' );

		$this->assertEquals(
			new ItemSearchResults(),
			$this->newEngine()->searchItemByLabel( $searchTerm, $languageCode )
		);
	}

	private function newEngine(): SqlTermStoreSearchEngine {
		return new SqlTermStoreSearchEngine(
			$this->matchingTermsLookup,
			$this->termRetriever
		);
	}
}
