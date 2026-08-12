<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use GraphQL\Deferred;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearch;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchRequest;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchResponse;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\UseCaseErrorType;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemSearchResultSet;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\GraphQLError;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\PaginationCursorCodec;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\QueryContext;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\ItemResolver;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\SearchItemsResolver;
use Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL\SearchEnabledTestTrait;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers\SearchItemsResolver
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SearchItemsResolverTest extends MediaWikiIntegrationTestCase {

	use SearchEnabledTestTrait;
	use PaginationCursorCodec;

	public function testResolveWithCursor(): void {
		$this->simulateSearchEnabled();

		$cursor = $this->encodeOffsetAsCursor( 10 );
		$facetedItemSearch = $this->createMock( FacetedItemSearch::class );
		$facetedItemSearch->expects( $this->once() )
			->method( 'execute' )
			->with( new FacetedItemSearchRequest( [ 'property' => 'P1' ], 50, 10 ) )
			->willReturn( new FacetedItemSearchResponse( new ItemSearchResultSet( [], 0 ) ) );

		$result = $this->newResolver( $facetedItemSearch )
			->resolve( [ 'property' => 'P1' ], 50, $cursor, new QueryContext() );

		$this->assertSame( [
			'edges' => [],
			'pageInfo' => [ 'endCursor' => null, 'hasPreviousPage' => true, 'hasNextPage' => false, 'startCursor' => null ],
		], $result );
	}

	public function testResolveWithoutCursor(): void {
		$this->simulateSearchEnabled();

		$cursor = null;
		$facetedItemSearch = $this->createMock( FacetedItemSearch::class );
		$facetedItemSearch->expects( $this->once() )
			->method( 'execute' )
			->with( new FacetedItemSearchRequest( [ 'property' => 'P1' ], 50, 0 ) )
			->willReturn( new FacetedItemSearchResponse( new ItemSearchResultSet( [], 0 ) ) );

		$result = $this->newResolver( $facetedItemSearch )
			->resolve( [ 'property' => 'P1' ], 50, $cursor, new QueryContext );

		$this->assertSame( [
			'edges' => [],
			'pageInfo' => [ 'endCursor' => null, 'hasPreviousPage' => false, 'hasNextPage' => false, 'startCursor' => null ],
		], $result );
	}

	public function testGivenInvalidQueryUseCaseError_rethrowsAsInvalidQuery(): void {
		$this->simulateSearchEnabled();

		$facetedItemSearch = $this->createStub( FacetedItemSearch::class );
		$facetedItemSearch->method( 'execute' )
			->willThrowException( new UseCaseError( UseCaseErrorType::INVALID_SEARCH_QUERY, 'some reason' ) );

		$this->expectException( GraphQLError::class );
		$this->expectExceptionMessage( 'Invalid search query: some reason' );

		$this->newResolver( $facetedItemSearch )->resolve( [ 'property' => 'P1' ], 50, null, new QueryContext() );
	}

	public function testHandlesSearchNotAvailable(): void {
		$this->simulateSearchEnabled( false );

		$facetedItemSearch = $this->createStub( FacetedItemSearch::class );
		$facetedItemSearch->expects( $this->never() )
		->method( 'execute' )->willReturn( $this->createStub( FacetedItemSearchResponse::class ) );

		$this->expectException( GraphQLError::class );
		$this->expectExceptionMessage( 'Search is not available due to insufficient server configuration' );

		$this->newResolver( $facetedItemSearch )->resolve( [ 'property' => 'P1' ], 50, null, new QueryContext() );
	}

	public function testHandlesDeletedItems(): void {
		$this->simulateSearchEnabled();
		$availableItemId = 'Q1';
		$deletedItemId = 'Q999';
		$itemIds = [ $availableItemId, $deletedItemId ];

		$searchResults = array_map(
			fn( string $id ) => new ItemSearchResult( new ItemId( $id ) ),
			$itemIds
		);

		$facetedItemSearch = $this->createMock( FacetedItemSearch::class );
		$facetedItemSearch->method( 'execute' )
			->willReturn( new FacetedItemSearchResponse(
				new ItemSearchResultSet( $searchResults, count( $searchResults ) )
			) );

		$context = new QueryContext();
		$itemResolver = $this->createMock( ItemResolver::class );
		$itemPromises = [
			$availableItemId => $this->createStub( Deferred::class ),
			$deletedItemId => $this->createStub( Deferred::class ),
		];
		$itemResolver->expects( $this->exactly( 2 ) )
			->method( 'resolveItem' )
			->willReturnMap(
				[
					[ $availableItemId, $context, false, $itemPromises[$availableItemId] ],
					[ $deletedItemId, $context, false, $itemPromises[$deletedItemId] ],
				]
			);

		$result = ( new SearchItemsResolver( $facetedItemSearch, $itemResolver ) )
			->resolve( [ 'property' => 'P1' ], 50, null, $context );

		$this->assertCount( 2, $result['edges'] );
		foreach ( $itemIds as $key => $id ) {
			$this->assertSame( $itemPromises[$id], $result['edges'][$key]['node'] );
		}
	}

	private function newResolver( FacetedItemSearch $facetedItemSearch ): SearchItemsResolver {
		return new SearchItemsResolver(
			$facetedItemSearch,
			$this->createStub( ItemResolver::class )
		);
	}

}
