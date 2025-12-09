<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use GraphQL\GraphQL;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearch;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchRequest;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchResponse;
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

		$resolver = new SearchItemsResolver( $facetedItemSearch, $this->getServiceContainer()->getExtensionRegistry() );
		$result = $resolver->resolve( [ 'property' => 'P1' ] );

		$this->assertSame( [], $result );
	}

	public function testHandlesSearchNotAvailable(): void {
		$this->simulateSearchEnabled( false );

		$facetedItemSearch = $this->createStub( FacetedItemSearch::class );
		$facetedItemSearch->expects( $this->never() )
		->method( 'execute' )->willReturn( $this->createStub( FacetedItemSearchResponse::class ) );

		$this->expectException( SearchNotAvailable::class );
		$this->expectExceptionMessage( 'Search is not available due to insufficient server configuration' );

		( new SearchItemsResolver(
			$facetedItemSearch,
			$this->getServiceContainer()->getExtensionRegistry()
		) )->resolve( [ 'property' => 'P1' ] );
	}

}
