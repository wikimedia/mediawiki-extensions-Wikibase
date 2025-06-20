<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Infrastructure\DataAccess;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\MatchingTermsLookup;
use Wikibase\Lib\TermIndexEntry;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\Domains\Search\Domain\Model\Description;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Model\Label;
use Wikibase\Repo\Domains\Search\Domain\Model\MatchedData;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResults;
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

	public function testGivenSearchResultForItemLabel(): void {
		$searchTerm = 'potato';
		$languageCode = 'en';

		$expectedSearchResult = new ItemSearchResult(
			new ItemId( 'Q123' ),
			new Label( 'en', 'potato' ),
			new Description( 'en', 'staple food' ),
			new MatchedData( TermTypes::TYPE_LABEL, 'en', 'potato' )
		);

		$matchingTermsLookup = $this->createMock( MatchingTermsLookup::class );
		$matchingTermsLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->willReturn(
				[ $this->newTermIndexEntry( TermTypes::TYPE_LABEL, new ItemId( 'Q123' ), 'en', 'potato' ) ]
			);

		$termRetriever = $this->createMock( TermRetriever::class );
		// we will not call TermRetriever::getLabel() because the index entry is of type label already
		$termRetriever->expects( $this->never() )
			->method( 'getLabel' );
		$termRetriever->expects( $this->once() )
			->method( 'getDescription' )
			->with( 'Q123', $languageCode )
			->willReturn( new Description( 'en', 'staple food' ) );

		$this->assertEquals(
			new ItemSearchResults( $expectedSearchResult ),
			$this->newEngine( matchingTermsLookup: $matchingTermsLookup, termRetriever: $termRetriever )
				->searchItemByLabel( $searchTerm, $languageCode, 10, 0 )
		);
	}

	public function testGivenSearchResultForPropertyLabel(): void {
		$searchTerm = 'instance of';
		$languageCode = 'en';

		$expectedSearchResult = new PropertySearchResult(
			new NumericPropertyId( 'P123' ),
			new Label( 'en', 'instance of' ),
			new Description( 'en', 'the class of which this subject is a particular example and member' ),
			new MatchedData( TermTypes::TYPE_LABEL, 'en', 'instance of' )
		);

		$matchingTermsLookup = $this->createMock( MatchingTermsLookup::class );
		$matchingTermsLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->willReturn(
				[ $this->newTermIndexEntry( TermTypes::TYPE_LABEL, new NumericPropertyId( 'P123' ), 'en', 'instance of' ) ]
			);

		$termRetriever = $this->createMock( TermRetriever::class );
		// we will not call TermRetriever::getLabel() because the index entry is of type label already
		$termRetriever->expects( $this->never() )
			->method( 'getLabel' );
		$termRetriever->expects( $this->once() )
			->method( 'getDescription' )
			->with( 'P123', $languageCode )
			->willReturn( new Description( 'en', 'the class of which this subject is a particular example and member' ) );

		$this->assertEquals(
			new PropertySearchResults( $expectedSearchResult ),
			$this->newEngine( matchingTermsLookup: $matchingTermsLookup, termRetriever: $termRetriever )
				->searchPropertyByLabel( $searchTerm, $languageCode, 10, 0 )
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

		$matchingTermsLookup = $this->createMock( MatchingTermsLookup::class );
		$matchingTermsLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->willReturn(
				[ $this->newTermIndexEntry( TermTypes::TYPE_ALIAS, new ItemId( 'Q123' ), 'en', 'spud' ) ]
			);

		$termRetriever = $this->createMock( TermRetriever::class );
		$termRetriever->expects( $this->once() )
			->method( 'getLabel' )
			->with( 'Q123', $languageCode )
			->willReturn( new Label( 'en', 'potato' ) );
		$termRetriever->expects( $this->once() )
			->method( 'getDescription' )
			->with( 'Q123', $languageCode )
			->willReturn( new Description( 'en', 'staple food' ) );

		$this->assertEquals(
			new ItemSearchResults( $expectedSearchResult ),
			$this->newEngine( matchingTermsLookup: $matchingTermsLookup, termRetriever: $termRetriever )->searchItemByLabel(
				$searchTerm,
				$languageCode,
				10,
				0
			)
		);
	}

	public function testGivenSearchResultForItemId(): void {
		$itemId = new ItemId( 'Q1' );
		$languageCode = 'en';
		$label = 'First Item';
		$description = 'some description';

		$expectedSearchResult = new ItemSearchResult(
			$itemId,
			new Label( $languageCode, $label ),
			new Description( $languageCode, $description ),
			new MatchedData( 'entityId', null, "$itemId" )
		);

		$languageFallbackChain = $this->createStub( TermLanguageFallbackChain::class );
		$languageFallbackChain->method( 'extractPreferredValue' )
			->willReturnOnConsecutiveCalls(
				[ 'language' => $languageCode, 'value' => $label ],
				[ 'language' => $languageCode, 'value' => $description ]
			);

		$entityLookup = $this->newMockEntityLookup(
			$itemId,
			NewItem::withId( "$itemId" )
				->andLabel( $languageCode, $label )
				->andDescription( $languageCode, $description )
				->build()
		);

		$this->assertEquals(
			new ItemSearchResults( $expectedSearchResult ),
			$this->newEngine(
				languageFallbackChain: $languageFallbackChain,
				entityLookup: $entityLookup
			)->searchItemByLabel( "$itemId", $languageCode, 10, 0 )
		);
	}

	public function testGivenItemIdResult_limitIsAdjusted(): void {
		$searchTerm = 'Q42';
		$limit = 5;

		$entityLookup = $this->newMockEntityLookup( new ItemId( 'Q42' ), NewItem::withId( 'Q42' )->build() );
		$matchingTermsLookup = $this->createMock( MatchingTermsLookup::class );
		$matchingTermsLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->with( $searchTerm,
				Item::ENTITY_TYPE,
				[],
				[ TermTypes::TYPE_LABEL, TermTypes::TYPE_ALIAS ],
				// expect reduced limit, because one result was found by ID
				[ 'LIMIT' => $limit - 1, 'OFFSET' => 0 ]
			)->willReturn( array_map(
				fn( $id ) => $this->newTermIndexEntry( TermTypes::TYPE_LABEL, new ItemId( $id ), 'en', 'Q42' ),
				[ 'Q1', 'Q2', 'Q3', 'Q4' ]
			) );

		$this->assertCount(
			$limit,
			$this->newEngine( entityLookup: $entityLookup, matchingTermsLookup: $matchingTermsLookup )
				->searchItemByLabel( $searchTerm, 'en', $limit, 0 )
		);
	}

	public function testGivenItemIdResult_offsetIsAdjusted(): void {
		$searchTerm = 'Q42';
		$offset = 5;

		$entityLookup = $this->newMockEntityLookup( new ItemId( 'Q42' ), NewItem::withId( 'Q42' )->build() );
		$matchingTermsLookup = $this->createMock( MatchingTermsLookup::class );
		$matchingTermsLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->with( $searchTerm,
				Item::ENTITY_TYPE,
				[],
				[ TermTypes::TYPE_LABEL, TermTypes::TYPE_ALIAS ],
				// expect reduced offset, because one result by ID was added to page 1
				[ 'LIMIT' => 5, 'OFFSET' => $offset - 1 ]
			)->willReturn( array_map(
				fn( $id ) => $this->newTermIndexEntry( TermTypes::TYPE_LABEL, new ItemId( $id ), 'en', 'Q42' ),
				[ 'Q5', 'Q6', 'Q7', 'Q8', 'Q9' ]
			) );

		$this->assertCount(
			5,
			$this->newEngine( entityLookup: $entityLookup, matchingTermsLookup: $matchingTermsLookup )
				->searchItemByLabel( $searchTerm, 'en', 5, $offset )
		);
	}

	public function testGivenSearchResultForPropertyId(): void {
		$propertyId = new NumericPropertyId( 'P1' );
		$languageCode = 'en';
		$label = 'First Property';
		$description = 'some description';

		$expectedSearchResult = new PropertySearchResult(
			$propertyId,
			new Label( $languageCode, $label ),
			new Description( $languageCode, $description ),
			new MatchedData( 'entityId', null, "$propertyId" )
		);

		$languageFallbackChain = $this->createMock( TermLanguageFallbackChain::class );
		$languageFallbackChain->method( 'extractPreferredValue' )
			->willReturnOnConsecutiveCalls(
				[ 'language' => $languageCode, 'value' => $label ],
				[ 'language' => $languageCode, 'value' => $description ]
			);

		$entityLookup = $this->newMockEntityLookup(
			$propertyId,
			new Property(
				$propertyId,
				new Fingerprint(
					new TermList( [ new Term( $languageCode, $label ) ] ),
					new TermList( [ new Term( $languageCode, $label ) ] )
				),
				'dataTypeId'
			)
		);

		$this->assertEquals(
			new PropertySearchResults( $expectedSearchResult ),
			$this->newEngine( languageFallbackChain: $languageFallbackChain, entityLookup: $entityLookup )
				->searchPropertyByLabel( "$propertyId", $languageCode, 10, 0 )
		);
	}

	public function testGivenPropertyIdResult_limitIsAdjusted(): void {
		$searchTerm = 'P1';
		$limit = 5;

		$entityLookup = $this->newMockEntityLookup(
			new NumericPropertyId( 'P1' ),
			new Property( new NumericPropertyId( 'P1' ), null, 'dataTypeId' )
		);
		$matchingTermsLookup = $this->createMock( MatchingTermsLookup::class );
		$matchingTermsLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->with( $searchTerm,
				Property::ENTITY_TYPE,
				[],
				[ TermTypes::TYPE_LABEL, TermTypes::TYPE_ALIAS ],
				// expect reduced limit, because one result was found by ID
				[ 'LIMIT' => $limit - 1, 'OFFSET' => 0 ]
			)->willReturn( array_map(
				fn( $id ) => $this->newTermIndexEntry( TermTypes::TYPE_LABEL, new NumericPropertyId( $id ), 'en', 'P1' ),
				[ 'P1', 'P2', 'P3', 'P4' ]
			) );

		$this->assertCount(
			$limit,
			$this->newEngine( entityLookup: $entityLookup, matchingTermsLookup: $matchingTermsLookup )
				->searchPropertyByLabel( $searchTerm, 'en', $limit, 0 )
		);
	}

	public function testGivenPropertyIdResult_offsetIsAdjusted(): void {
		$searchTerm = 'P1';
		$offset = 5;

		$entityLookup = $this->newMockEntityLookup(
			new NumericPropertyId( 'P1' ),
			new Property( new NumericPropertyId( 'P1' ), null, 'dataTypeId' )
		);
		$matchingTermsLookup = $this->createMock( MatchingTermsLookup::class );
		$matchingTermsLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->with( $searchTerm,
				Property::ENTITY_TYPE,
				[],
				[ TermTypes::TYPE_LABEL, TermTypes::TYPE_ALIAS ],
				// expect reduced offset, because one result by ID was added to page 1
				[ 'LIMIT' => 5, 'OFFSET' => $offset - 1 ]
			)->willReturn( array_map(
				fn( $id ) => $this->newTermIndexEntry( TermTypes::TYPE_LABEL, new NumericPropertyId( $id ), 'en', 'P1' ),
				[ 'P5', 'P6', 'P7', 'P8', 'P9' ]
			) );

		$this->assertCount(
			5,
			$this->newEngine( entityLookup: $entityLookup, matchingTermsLookup: $matchingTermsLookup )
				->searchPropertyByLabel( $searchTerm, 'en', 5, $offset )
		);
	}

	public function testResultPagination(): void {
		$results = [];
		foreach ( [ TermTypes::TYPE_LABEL, TermTypes::TYPE_ALIAS ] as $type ) {
			for ( $i = 1; $i <= 7; $i++ ) {
				$results[] = $this->newTermIndexEntry( $type, new ItemId( "Q{$i}" ) );
			}
		}

		$matchingTermsLookup = $this->createMock( MatchingTermsLookup::class );
		$matchingTermsLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->with(
				'some query',
				Item::ENTITY_TYPE,
				[],
				[ TermTypes::TYPE_LABEL, TermTypes::TYPE_ALIAS ],
				[ 'LIMIT' => 5, 'OFFSET' => 2 ],
			)
			->willReturn( array_slice( $results, 2, 5 ) );

		$searchResult = $this->newEngine( matchingTermsLookup: $matchingTermsLookup )
			->searchItemByLabel( 'some query', 'en', 5, 2 );
		$this->assertCount( 5, $searchResult );
		$this->assertEquals( new ItemId( 'Q3' ), $searchResult[0]->getItemId() );
	}

	public function testGivenSearchFails_returnsNoResults(): void {
		$searchTerm = 'potato';
		$languageCode = 'en';

		$matchingTermsLookup = $this->createMock( MatchingTermsLookup::class );
		$matchingTermsLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->willReturn( [] );

		$termRetriever = $this->createMock( TermRetriever::class );
		$termRetriever->expects( $this->never() )
			->method( 'getLabel' );
		$termRetriever->expects( $this->never() )
			->method( 'getDescription' );

		$this->assertEquals(
			new ItemSearchResults(),
			$this->newEngine( matchingTermsLookup: $matchingTermsLookup, termRetriever: $termRetriever )->searchItemByLabel(
				$searchTerm,
				$languageCode,
				5,
				0
			)
		);
	}

	private function newTermIndexEntry( string $type, EntityId $id, string $language = 'en', ?string $text = null ): TermIndexEntry {
		return new TermIndexEntry( [
			TermIndexEntry::FIELD_TYPE => $type,
			TermIndexEntry::FIELD_ENTITY => $id,
			TermIndexEntry::FIELD_LANGUAGE => $language,
			TermIndexEntry::FIELD_TEXT => $text ?: 'result-' . bin2hex( random_bytes( 5 ) ),
		] );
	}

	private function newEngine(
		?MatchingTermsLookup $matchingTermsLookup = null,
		?EntityLookup $entityLookup = null,
		?TermRetriever $termRetriever = null,
		?TermLanguageFallbackChain $languageFallbackChain = null
	): SqlTermStoreSearchEngine {
		$languageFallbackChainFactory = $this->createStub( LanguageFallbackChainFactory::class );
		$languageFallbackChainFactory
			->method( 'newFromLanguageCode' )
			->willReturn( $languageFallbackChain ?? $this->createStub( TermLanguageFallbackChain::class ) );

		return new SqlTermStoreSearchEngine(
			$matchingTermsLookup ?? $this->createStub( MatchingTermsLookup::class ),
			$entityLookup ?? $this->createStub( EntityLookup::class ),
			$termRetriever ?? $this->createStub( TermRetriever::class ),
			$languageFallbackChainFactory
		);
	}

	private function newMockEntityLookup( EntityId $entityId, EntityDocument $entity ): EntityLookup {
		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup->expects( $this->once() )
			->method( 'getEntity' )
			->with( $entityId )
			->willReturn( $entity );

		return $entityLookup;
	}
}
