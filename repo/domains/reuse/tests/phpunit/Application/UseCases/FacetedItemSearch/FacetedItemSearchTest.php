<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Application\UseCases\FacetedItemSearch;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearch;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchRequest;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchResponse;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchValidator;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\UseCaseErrorType;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValueFilter;
use Wikibase\Repo\Domains\Reuse\Domain\Services\FacetedItemSearchEngine;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearch
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FacetedItemSearchTest extends TestCase {

	private FacetedItemSearchValidator $validator;
	private FacetedItemSearchEngine $searchEngine;

	public function setUp(): void {
		$this->validator = $this->createStub( FacetedItemSearchValidator::class );
		$this->searchEngine = $this->createStub( FacetedItemSearchEngine::class );
	}

	public function testSuccess(): void {
		$request = new FacetedItemSearchRequest( [ 'query' => [ 'property' => 'P1' ] ] );
		$searchQuery = new PropertyValueFilter( new NumericPropertyId( 'P1' ) );
		$this->validator = $this->createMock( FacetedItemSearchValidator::class );
		$this->validator->expects( $this->once() )
			->method( 'validate' )
			->with( $request );
		$this->validator->expects( $this->once() )
			->method( 'getValidatedQuery' )
			->willReturn( $searchQuery );

		$searchResult = [ new ItemSearchResult( new ItemId( 'Q1' ) ) ];
		$this->searchEngine = $this->createMock( FacetedItemSearchEngine::class );
		$this->searchEngine->expects( $this->once() )
			->method( 'search' )
			->with( $searchQuery )
			->willReturn( $searchResult );

		$this->assertEquals(
			new FacetedItemSearchResponse( $searchResult ),
			$this->newUseCase()->execute( $request )
		);
	}

	public function testGivenInvalidQuery_throws(): void {
		$usecaseError = new UseCaseError( UseCaseErrorType::INVALID_SEARCH_QUERY, 'error message' );
		$this->validator = $this->createStub( FacetedItemSearchValidator::class );
		$this->validator->method( 'validate' )
			->willThrowException( $usecaseError );

		$this->searchEngine = $this->createMock( FacetedItemSearchEngine::class );
		$this->searchEngine
			->expects( $this->never() )
			->method( 'search' );

		$this->expectExceptionObject( $usecaseError );
		$this->newUseCase()->execute( new FacetedItemSearchRequest( [ 'query' => 'invalid' ] ) );
	}

	private function newUseCase(): FacetedItemSearch {
		return new FacetedItemSearch(
			$this->validator,
			$this->searchEngine
		);
	}

}
