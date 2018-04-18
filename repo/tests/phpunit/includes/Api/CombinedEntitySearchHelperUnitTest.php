<?php

namespace Wikibase\Repo\Tests\Api;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\CombinedEntitySearchHelper;
use Wikibase\Repo\Api\EntitySearchHelper;

/**
 * @covers \Wikibase\Repo\Api\CombinedEntitySearchHelper
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class CombinedEntitySearchHelperUnitTest extends \PHPUnit\Framework\TestCase {

	use \PHPUnit4And6Compat;

	public function testInternalSearchHelperReceivesCorrectParameters() {
		$q33 = [ 'Q33' => new TermSearchResult( new Term( 'en', 'foo33' ), 'match', new ItemId( 'Q33' ) ) ];

		$mock1 = $this->getMock( EntitySearchHelper::class );
		$mock1->expects( $this->exactly( 1 ) )
			->method( 'getRankedSearchResults' )
			->with( 'a', 'b', 'c', 1, true )
			->willReturn( $q33 );

		$helper = new CombinedEntitySearchHelper( [ $mock1 ] );
		$helper->getRankedSearchResults( 'a', 'b', 'c', 1, true );
	}

	public function testInternalSearchHelperExecutedInCorrectOrderAndOutputCorrectly() {
		$q33 = [ 'Q33' => new TermSearchResult( new Term( 'en', 'foo33' ), 'match', new ItemId( 'Q33' ) ) ];
		$q1 = [ 'Q1' => new TermSearchResult( new Term( 'en', 'foo1' ), 'match', new ItemId( 'Q1' ) ) ];

		$mock1 = $this->getMock( EntitySearchHelper::class );
		$mock1->expects( $this->exactly( 1 ) )
			->method( 'getRankedSearchResults' )
			->willReturn( $q33 );
		$mock2 = $this->getMock( EntitySearchHelper::class );
		$mock2->expects( $this->exactly( 1 ) )
			->method( 'getRankedSearchResults' )
			->willReturn( $q1 );

		$helper = new CombinedEntitySearchHelper( [ $mock1, $mock2 ] );
		$result = $helper->getRankedSearchResults( '', '', '', 2, false );

		$this->assertEquals(
			[
				'Q33' => $q33['Q33'],
				'Q1' => $q1['Q1'],
			],
			$result
		);
	}

	public function testInternalSearchHelperStopsWhenLimitReached() {
		$q33 = [ 'Q33' => new TermSearchResult( new Term( 'en', 'foo33' ), 'match', new ItemId( 'Q33' ) ) ];
		$q1 = [ 'Q1' => new TermSearchResult( new Term( 'en', 'foo1' ), 'match', new ItemId( 'Q1' ) ) ];

		$mock1 = $this->getMock( EntitySearchHelper::class );
		$mock1->expects( $this->exactly( 1 ) )
			->method( 'getRankedSearchResults' )
			->willReturn( $q33 );
		$mock2 = $this->getMock( EntitySearchHelper::class );
		$mock2->expects( $this->exactly( 0 ) )
			->method( 'getRankedSearchResults' )
			->willReturn( $q1 );

		$helper = new CombinedEntitySearchHelper( [ $mock1, $mock2 ] );
		$result = $helper->getRankedSearchResults( '', '', '', 1, false );

		$this->assertArrayNotHasKey( 'Q1', $result );
		$this->assertArrayHasKey( 'Q33', $result );
		$this->assertCount( 1, $result );
	}

}
