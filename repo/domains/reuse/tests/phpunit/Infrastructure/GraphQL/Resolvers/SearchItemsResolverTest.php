<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use GraphQL\GraphQL;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearch;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchRequest;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchResponse;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\UseCaseErrorType;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemSearchResultSet;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\GraphQLError;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\PaginationCursorCodec;
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

	public static function setUpBeforeClass(): void {
		if ( !class_exists( GraphQL::class ) ) {
			self::markTestSkipped( 'Needs webonyx/graphql-php to run' );
		}
	}

	public function testResolveWithCursor(): void {
		$this->simulateSearchEnabled();

		$cursor = $this->encodeOffsetAsCursor( 10 );
		$facetedItemSearch = $this->createMock( FacetedItemSearch::class );
		$facetedItemSearch->expects( $this->once() )
			->method( 'execute' )
			->with( new FacetedItemSearchRequest( [ 'property' => 'P1' ], 50, 10 ) )
			->willReturn( new FacetedItemSearchResponse( new ItemSearchResultSet( [], 0 ) ) );

		$result = $this->newResolver( $facetedItemSearch )
			->resolve( [ 'property' => 'P1' ], 50, $cursor );

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
			->resolve( [ 'property' => 'P1' ], 50, $cursor );

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

		$this->newResolver( $facetedItemSearch )->resolve( [ 'property' => 'P1' ], 50, null );
	}

	public function testHandlesSearchNotAvailable(): void {
		$this->simulateSearchEnabled( false );

		$facetedItemSearch = $this->createStub( FacetedItemSearch::class );
		$facetedItemSearch->expects( $this->never() )
		->method( 'execute' )->willReturn( $this->createStub( FacetedItemSearchResponse::class ) );

		$this->expectException( GraphQLError::class );
		$this->expectExceptionMessage( 'Search is not available due to insufficient server configuration' );

		$this->newResolver( $facetedItemSearch )->resolve( [ 'property' => 'P1' ], 50, null );
	}

	private function newResolver( FacetedItemSearch $facetedItemSearch ): SearchItemsResolver {
		return new SearchItemsResolver(
			$facetedItemSearch,
			$this->getServiceContainer()->getExtensionRegistry()
		);
	}

}
