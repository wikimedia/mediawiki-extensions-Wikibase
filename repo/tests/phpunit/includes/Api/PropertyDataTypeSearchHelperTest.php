<?php

namespace Wikibase\Repo\Tests\Api;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\Api\PropertyDataTypeSearchHelper;

/**
 * @covers \Wikibase\Repo\Api\PropertyDataTypeSearchHelper
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0-or-later
 */
class PropertyDataTypeSearchHelperTest extends \PHPUnit\Framework\TestCase {

	public function testGetRankedSearchResults_delegatesAndAddsDataTypeMetaData() {
		$property1 = new NumericPropertyId( 'P123' );
		$property1Datatype = 'string';
		$property2 = new NumericPropertyId( 'P321' );
		$property2Datatype = 'url';

		$property1TermSearchResult = new TermSearchResult(
			new Term( 'en', 'foo' ),
			'label',
			$property1,
			new Term( 'en', 'display label' ),
			new Term( 'en', 'display description' ),
			[ 'some' => 'meta data' ]
		);

		$property2TermSearchResult = new TermSearchResult(
			new Term( 'en', 'foo' ),
			'label',
			$property2
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
				$property2TermSearchResult,
			] );

		$dataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );

		$dataTypeLookup->expects( $this->exactly( 2 ) )
			->method( 'getDataTypeIdForProperty' )
			->withConsecutive(
				[ $property1 ],
				[ $property2 ]
			)
			->willReturnOnConsecutiveCalls(
				$property1Datatype,
				$property2Datatype
			);

		$results = ( new PropertyDataTypeSearchHelper( $searchHelper, $dataTypeLookup ) )
			->getRankedSearchResults( $searchText, $searchLanguageCode, $searchEntityType, $searchLimit, $searchStrictLanguage, null );

		$this->assertSame( $property1TermSearchResult->getDisplayDescription(), $results[0]->getDisplayDescription() );
		$this->assertSame( $property1TermSearchResult->getDisplayLabel(), $results[0]->getDisplayLabel() );
		$this->assertSame( $property1TermSearchResult->getEntityId(), $results[0]->getEntityId() );
		$this->assertSame( $property1TermSearchResult->getMatchedTerm(), $results[0]->getMatchedTerm() );
		$this->assertSame( $property1TermSearchResult->getMatchedTermType(), $results[0]->getMatchedTermType() );
		$this->assertEquals(
			array_merge( $property1TermSearchResult->getMetaData(), [
				PropertyDataTypeSearchHelper::DATATYPE_META_DATA_KEY => $property1Datatype,
			] ),
			$results[0]->getMetaData()
		);

		$this->assertSame( $property2TermSearchResult->getDisplayDescription(), $results[1]->getDisplayDescription() );
		$this->assertSame( $property2TermSearchResult->getDisplayLabel(), $results[1]->getDisplayLabel() );
		$this->assertSame( $property2TermSearchResult->getEntityId(), $results[1]->getEntityId() );
		$this->assertSame( $property2TermSearchResult->getMatchedTerm(), $results[1]->getMatchedTerm() );
		$this->assertSame( $property2TermSearchResult->getMatchedTermType(), $results[1]->getMatchedTermType() );
		$this->assertEquals(
			array_merge( $property2TermSearchResult->getMetaData(), [
				PropertyDataTypeSearchHelper::DATATYPE_META_DATA_KEY => $property2Datatype,
			] ),
			$results[1]->getMetaData()
		);
	}

}
