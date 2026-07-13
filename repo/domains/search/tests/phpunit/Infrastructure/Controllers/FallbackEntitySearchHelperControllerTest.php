<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Infrastructure\Controllers;

use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\Domains\Search\Domain\Model\User;
use Wikibase\Repo\Domains\Search\Infrastructure\Controllers\FallbackEntitySearchHelperController;
use Wikibase\Repo\Domains\Search\Infrastructure\Controllers\WbSearchEntitiesRequest;

/**
 * @covers \Wikibase\Repo\Domains\Search\Infrastructure\Controllers\FallbackEntitySearchHelperController
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FallbackEntitySearchHelperControllerTest extends TestCase {

	public function testOverFetchesAndReturnsRequestedPageWithHasMore(): void {
		// offset 0 + limit 5 + 1 = 6 requested; the 6th row signals "more".
		$overFetched = array_map( fn( int $i ) => $this->newTermSearchResult( "Q$i" ), range( 1, 6 ) );

		$searchHelper = $this->createMock( EntitySearchHelper::class );
		$searchHelper->expects( $this->once() )
			->method( 'getRankedSearchResults' )
			->with( 'foo', 'en', 'item', 6, true, 'default' )
			->willReturn( $overFetched );

		$response = $this->newController( $searchHelper )
			->search( new WbSearchEntitiesRequest( 'foo', 'en', 'en', 5, true, 'default', User::newAnonymous() ) );

		$this->assertSame( array_slice( $overFetched, 0, 5 ), $response->results );
		$this->assertTrue( $response->hasMore );
	}

	public function testAppliesOffset(): void {
		// offset 2 + limit 5 + 1 = 8 requested; page is rows [2..6], row 7 signals "more".
		$overFetched = array_map( fn( int $i ) => $this->newTermSearchResult( "Q$i" ), range( 1, 8 ) );

		$searchHelper = $this->createMock( EntitySearchHelper::class );
		$searchHelper->expects( $this->once() )
			->method( 'getRankedSearchResults' )
			->with( 'foo', 'en', 'item', 8, true, 'default' )
			->willReturn( $overFetched );

		$response = $this->newController( $searchHelper )
			->search( new WbSearchEntitiesRequest( 'foo', 'en', 'en', 5, true, 'default', User::newAnonymous(), 2 ) );

		$this->assertSame( array_slice( $overFetched, 2, 5 ), $response->results );
		$this->assertTrue( $response->hasMore );
	}

	public function testHasMoreIsFalseWhenNoExtraResults(): void {
		// Fewer than the over-fetch limit come back, so there is no further page.
		$results = array_map( fn( int $i ) => $this->newTermSearchResult( "Q$i" ), range( 1, 5 ) );

		$searchHelper = $this->createMock( EntitySearchHelper::class );
		$searchHelper->expects( $this->once() )
			->method( 'getRankedSearchResults' )
			->with( 'foo', 'en', 'item', 6, true, 'default' )
			->willReturn( $results );

		$response = $this->newController( $searchHelper )
			->search( new WbSearchEntitiesRequest( 'foo', 'en', 'en', 5, true, 'default', User::newAnonymous() ) );

		$this->assertSame( $results, $response->results );
		$this->assertFalse( $response->hasMore );
	}

	private function newController( EntitySearchHelper $searchHelper ): FallbackEntitySearchHelperController {
		return new FallbackEntitySearchHelperController(
			'item',
			$searchHelper,
			$this->createStub( EntitySourceLookup::class )
		);
	}

	private function newTermSearchResult( string $id ): TermSearchResult {
		// Seed the concept-uri metadata key so ConceptUriSearchHelper passes the result through unchanged.
		return new TermSearchResult(
			new Term( 'en', $id ),
			'label',
			new ItemId( $id ),
			new Term( 'en', $id ),
			null,
			[ TermSearchResult::CONCEPTURI_META_DATA_KEY => "http://www.wikidata.org/entity/$id" ]
		);
	}

}
