<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use GraphQL\GraphQL;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearch;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchRequest;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchResponse;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\UseCaseErrorType;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\InvalidSearchQuery;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\SearchNotAvailable;
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

	public static function setUpBeforeClass(): void {
		if ( !class_exists( GraphQL::class ) ) {
			self::markTestSkipped( 'Needs webonyx/graphql-php to run' );
		}
	}

	public function testResolve(): void {
		$this->simulateSearchEnabled();

		$facetedItemSearch = $this->createMock( FacetedItemSearch::class );
		$facetedItemSearch->expects( $this->once() )
			->method( 'execute' )
			->with( new FacetedItemSearchRequest( [ 'property' => 'P1' ] ) )
			->willReturn( new FacetedItemSearchResponse( [] ) );

		$result = $this->newResolver( $facetedItemSearch )
			->resolve( [ 'property' => 'P1' ] );

		$this->assertSame( [], $result );
	}

	public function testGivenInvalidQueryUseCaseError_rethrowsAsInvalidQuery(): void {
		$this->simulateSearchEnabled();

		$facetedItemSearch = $this->createStub( FacetedItemSearch::class );
		$facetedItemSearch->method( 'execute' )
			->willThrowException( new UseCaseError( UseCaseErrorType::INVALID_SEARCH_QUERY, 'some reason' ) );

		$this->expectException( InvalidSearchQuery::class );
		$this->expectExceptionMessage( 'Invalid search query: some reason' );

		$this->newResolver( $facetedItemSearch )->resolve( [ 'property' => 'P1' ] );
	}

	public function testHandlesSearchNotAvailable(): void {
		$this->simulateSearchEnabled( false );

		$facetedItemSearch = $this->createStub( FacetedItemSearch::class );
		$facetedItemSearch->expects( $this->never() )
		->method( 'execute' )->willReturn( $this->createStub( FacetedItemSearchResponse::class ) );

		$this->expectException( SearchNotAvailable::class );
		$this->expectExceptionMessage( 'Search is not available due to insufficient server configuration' );

		$this->newResolver( $facetedItemSearch )->resolve( [ 'property' => 'P1' ] );
	}

	private function newResolver( FacetedItemSearch $facetedItemSearch ): SearchItemsResolver {
		return new SearchItemsResolver(
			$facetedItemSearch,
			$this->getServiceContainer()->getExtensionRegistry()
		);
	}

}
