<?php

namespace Wikibase\Repo\Tests\Api;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\CombinedEntitySearchHelper;
use Wikibase\Repo\Api\FedPropertiesTypeDispatchingEntitySearchHelper;
use Wikibase\Repo\Api\TypeDispatchingEntitySearchHelper;

/**
 * @covers \Wikibase\Repo\Api\FedPropertiesTypeDispatchingEntitySearchHelper
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0-or-later
 */
class FedPropertiesTypeDispatchingEntitySearchHelperTest extends \PHPUnit\Framework\TestCase {

	public function testInternalSearchHelperReceivesCorrectParameters() {
		$q33 = [ 'Q33' => new TermSearchResult( new Term( 'en', 'thirty three' ), 'match', new ItemId( 'Q33' ) ) ];

		$federatedPropertiesEntitySearchHelper = $this->createMock( CombinedEntitySearchHelper::class );
		$typeDispatchingSearch = $this->createMock( TypeDispatchingEntitySearchHelper::class );
		$typeDispatchingSearch->expects( $this->atLeastOnce() )
			->method( 'getRankedSearchResults' )
			->with( 'some text', 'en', 'entity_type', 1, true )
			->willReturn( $q33 );

		$helper = new FedPropertiesTypeDispatchingEntitySearchHelper(
			$federatedPropertiesEntitySearchHelper,
			$typeDispatchingSearch
		);

		$helper->getRankedSearchResults( 'some text', 'en', 'entity_type', 1, true, null );
	}

	public function testInternalSearchHelperIsCombinedSearchHelperWhenEntityTypeIsProperty() {
		$p31 = [ 'P31' => new TermSearchResult( new Term( 'en', 'instance of' ), 'match', new NumericPropertyId( 'P31' ) ) ];
		$federatedPropertiesEntitySearchHelper = $this->createMock( CombinedEntitySearchHelper::class );
		$typeDispatchingSearch = $this->createMock( TypeDispatchingEntitySearchHelper::class );

		$federatedPropertiesEntitySearchHelper->expects( $this->atLeastOnce() )
			->method( 'getRankedSearchResults' )
			->with( 'some text', 'en', 'property', 1, true )
			->willReturn( $p31 );

		$helper = new FedPropertiesTypeDispatchingEntitySearchHelper(
			$federatedPropertiesEntitySearchHelper,
			$typeDispatchingSearch
		);

		$helper->getRankedSearchResults( 'some text', 'en', 'property', 1, true, null );

		$this->assertSame(
			$p31,
			$helper->getRankedSearchResults( 'some text', 'en', 'property', 1, true, null )
		);
	}

	public function testInternalSearchHelperIsTypeDispatchingSearchHelperWhenEntityTypeIsNotProperty() {
		$q33 = [ 'Q33' => new TermSearchResult( new Term( 'en', 'thirty three' ), 'match', new ItemId( 'Q33' ) ) ];
		$federatedPropertiesEntitySearchHelper = $this->createMock( CombinedEntitySearchHelper::class );
		$typeDispatchingSearch = $this->createMock( TypeDispatchingEntitySearchHelper::class );

		$typeDispatchingSearch->expects( $this->atLeastOnce() )
			->method( 'getRankedSearchResults' )
			->with( 'some text', 'en', 'potato', 1, true )
			->willReturn( $q33 );

		$helper = new FedPropertiesTypeDispatchingEntitySearchHelper(
			$federatedPropertiesEntitySearchHelper,
			$typeDispatchingSearch
		);

		$helper->getRankedSearchResults( 'some text', 'en', 'potato', 1, true, null );

		$this->assertSame(
			$q33,
			$helper->getRankedSearchResults( 'some text', 'en', 'potato', 1, true, null )
		);
	}

}
