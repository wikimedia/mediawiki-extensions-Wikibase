<?php

namespace Wikibase\Repo\Tests\Api;

use MediaWiki\Revision\SlotRecord;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Api\ConceptUriSearchHelper;
use Wikibase\Repo\Api\EntitySearchHelper;

/**
 * @covers \Wikibase\Repo\Api\ConceptUriSearchHelper
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0-or-later
 */
class ConceptUriSearchHelperTest extends \PHPUnit\Framework\TestCase {

	private function getEntitySourceLookup( string $sourceName = 'test' ): EntitySourceLookup {
		$subEntityTypesMapper = new SubEntityTypesMapper( [] );

		return new EntitySourceLookup( new EntitySourceDefinitions(
			[ new DatabaseEntitySource(
				$sourceName,
				false,
				[
					'item' => [ 'namespaceId' => 100, 'slot' => SlotRecord::MAIN ],
					'property' => [ 'namespaceId' => 200, 'slot' => SlotRecord::MAIN ],
				],
				'myConceptUriBase-',
				'',
				'',
				''
			) ],
			$subEntityTypesMapper
		), $subEntityTypesMapper );
	}

	public function testGetRankedSearchResults_delegatesAndAddsConceptUriWhenNotSet() {
		$property1 = new NumericPropertyId( 'P123' );
		$property1ConceptUri = 'myConceptUriBase-P123';

		$property1TermSearchResult = new TermSearchResult(
			new Term( 'en', 'foo' ),
			'label',
			$property1,
			new Term( 'en', 'display label' ),
			new Term( 'en', 'display description' ),
			[ 'some' => 'meta data' ]
		);

		$searchText = 'some';
		$searchLanguageCode = 'en';
		$searchEntityType = 'property';
		$searchLimit = 10;
		$searchStrictLanguage = true;

		$searchHelper = $this->createMock( EntitySearchHelper::class );
		$searchHelper->expects( $this->once() )
			->method( 'getRankedSearchResults' )
			->with( $searchText, $searchLanguageCode, $searchEntityType, $searchLimit, $searchStrictLanguage )
			->willReturn( [
				$property1TermSearchResult,
			] );

		$searchHelper = new ConceptUriSearchHelper(
			$searchHelper,
			$this->getEntitySourceLookup()
		);

		$results = $searchHelper->getRankedSearchResults(
			$searchText,
			$searchLanguageCode,
			$searchEntityType,
			$searchLimit,
			$searchStrictLanguage,
			null
		);

		$this->assertSame( $property1TermSearchResult->getDisplayDescription(), $results[0]->getDisplayDescription() );
		$this->assertSame( $property1TermSearchResult->getDisplayLabel(), $results[0]->getDisplayLabel() );
		$this->assertSame( $property1TermSearchResult->getEntityId(), $results[0]->getEntityId() );
		$this->assertSame( $property1TermSearchResult->getMatchedTerm(), $results[0]->getMatchedTerm() );
		$this->assertSame( $property1TermSearchResult->getMatchedTermType(), $results[0]->getMatchedTermType() );
		$this->assertEquals(
			array_merge( $property1TermSearchResult->getMetaData(), [
				ConceptUriSearchHelper::CONCEPTURI_META_DATA_KEY => $property1ConceptUri,
			] ),
			$results[0]->getMetaData()
		);
	}

	public function testGetRankedSearchResults_doesNotAddConceptUriWhenAlreadySet() {
		$property1 = new NumericPropertyId( 'P123' );
		$property1ConceptUri = 'alreadySet';

		$property1TermSearchResult = new TermSearchResult(
			new Term( 'en', 'foo' ),
			'label',
			$property1,
			new Term( 'en', 'display label' ),
			new Term( 'en', 'display description' ),
			[ ConceptUriSearchHelper::CONCEPTURI_META_DATA_KEY => 'alreadySet' ]
		);

		$searchText = 'some';
		$searchLanguageCode = 'en';
		$searchEntityType = 'property';
		$searchLimit = 10;
		$searchStrictLanguage = true;

		$searchHelper = $this->createMock( EntitySearchHelper::class );
		$searchHelper->expects( $this->once() )
			->method( 'getRankedSearchResults' )
			->with( $searchText, $searchLanguageCode, $searchEntityType, $searchLimit, $searchStrictLanguage )
			->willReturn( [
				$property1TermSearchResult,
			] );

		$searchHelper = new ConceptUriSearchHelper(
			$searchHelper,
			$this->getEntitySourceLookup()
		);

		$results = $searchHelper->getRankedSearchResults(
			$searchText,
			$searchLanguageCode,
			$searchEntityType,
			$searchLimit,
			$searchStrictLanguage,
			null
		);

		$this->assertSame( $property1TermSearchResult->getDisplayDescription(), $results[0]->getDisplayDescription() );
		$this->assertSame( $property1TermSearchResult->getDisplayLabel(), $results[0]->getDisplayLabel() );
		$this->assertSame( $property1TermSearchResult->getEntityId(), $results[0]->getEntityId() );
		$this->assertSame( $property1TermSearchResult->getMatchedTerm(), $results[0]->getMatchedTerm() );
		$this->assertSame( $property1TermSearchResult->getMatchedTermType(), $results[0]->getMatchedTermType() );
		$this->assertEquals(
			array_merge( $property1TermSearchResult->getMetaData(), [
				ConceptUriSearchHelper::CONCEPTURI_META_DATA_KEY => $property1ConceptUri,
			] ),
			$results[0]->getMetaData()
		);
	}

}
