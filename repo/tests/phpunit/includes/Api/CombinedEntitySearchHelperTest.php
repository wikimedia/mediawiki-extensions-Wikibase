<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use InvalidArgumentException;
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
class CombinedEntitySearchHelperTest extends \PHPUnit\Framework\TestCase {

	public function testWithInvalidConstructorArgs(): void {
		$this->expectException( InvalidArgumentException::class );

		new CombinedEntitySearchHelper( [
			$this->createStub( EntitySearchHelper::class ),
			'a potato',
		] );
	}

	public function testInternalSearchHelperReceivesCorrectParameters() {
		$q33 = [ 'Q33' => new TermSearchResult( new Term( 'en', 'foo33' ), 'match', new ItemId( 'Q33' ) ) ];

		$mock1 = $this->createMock( EntitySearchHelper::class );
		$mock1->expects( $this->once() )
			->method( 'getRankedSearchResults' )
			->with( 'a', 'b', 'c', 1, true )
			->willReturn( $q33 );

		$helper = new CombinedEntitySearchHelper( [ $mock1 ] );
		$helper->getRankedSearchResults( 'a', 'b', 'c', 1, true, null );
	}

	public function testInternalSearchHelperExecutedInCorrectOrderAndOutputCorrectly() {
		$q33 = [ 'Q33' => new TermSearchResult( new Term( 'en', 'foo33' ), 'match', new ItemId( 'Q33' ) ) ];
		$q1 = [ 'Q1' => new TermSearchResult( new Term( 'en', 'foo1' ), 'match', new ItemId( 'Q1' ) ) ];

		$mock1 = $this->createMock( EntitySearchHelper::class );
		$mock1->expects( $this->once() )
			->method( 'getRankedSearchResults' )
			->willReturn( $q33 );
		$mock2 = $this->createMock( EntitySearchHelper::class );
		$mock2->expects( $this->once() )
			->method( 'getRankedSearchResults' )
			->willReturn( $q1 );

		$helper = new CombinedEntitySearchHelper( [ $mock1, $mock2 ] );
		$result = $helper->getRankedSearchResults( '', '', '', 2, false, null );

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

		$mock1 = $this->createMock( EntitySearchHelper::class );
		$mock1->expects( $this->once() )
			->method( 'getRankedSearchResults' )
			->willReturn( $q33 );
		$mock2 = $this->createMock( EntitySearchHelper::class );
		$mock2->expects( $this->exactly( 0 ) )
			->method( 'getRankedSearchResults' )
			->willReturn( $q1 );

		$helper = new CombinedEntitySearchHelper( [ $mock1, $mock2 ] );
		$result = $helper->getRankedSearchResults( '', '', '', 1, false, null );

		$this->assertArrayNotHasKey( 'Q1', $result );
		$this->assertArrayHasKey( 'Q33', $result );
		$this->assertCount( 1, $result );
	}

	public function testInternalSearchHelperDoesNotDuplicateEntriesForSingleEntity() {
		$result1 = [ 'Q1' => new TermSearchResult( new Term( 'en', 'foo1' ), 'match1', new ItemId( 'Q1' ) ) ];
		$result2 = [ 'Q1' => new TermSearchResult( new Term( 'en', 'foo2' ), 'match2', new ItemId( 'Q1' ) ) ];

		$mock1 = $this->createMock( EntitySearchHelper::class );
		$mock1->expects( $this->once() )
			->method( 'getRankedSearchResults' )
			->willReturn( $result1 );
		$mock2 = $this->createMock( EntitySearchHelper::class );
		$mock2->expects( $this->once() )
			->method( 'getRankedSearchResults' )
			->willReturn( $result2 );

		$helper = new CombinedEntitySearchHelper( [ $mock1, $mock2 ] );
		$result = $helper->getRankedSearchResults( '', '', '', 2, false, null );

		$this->assertEquals(
			[
				'Q1' => $result1['Q1'],
			],
			$result
		);
	}

}
