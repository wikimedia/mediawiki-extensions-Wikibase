<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Application\UseCases\SimplePropertySearch;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearchRequest;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearchValidator;
use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResults;
use Wikibase\Repo\Domains\Search\Domain\Services\PropertySearchEngine;

/**
 * @covers \Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearch
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SimplePropertySearchTest extends TestCase {

	public function testCanExecute(): void {
		$query = 'needle';
		$language = 'en';
		$limit = 10;
		$offset = 0;
		$expectedResults = $this->createStub( PropertySearchResults::class );

		$validator = $this->createStub( SimplePropertySearchValidator::class );
		$searchEngine = $this->createMock( PropertySearchEngine::class );
		$searchEngine->expects( $this->once() )
			->method( 'searchPropertyByLabel' )
			->with( $query, $language )
			->willReturn( $expectedResults );

		$this->assertEquals(
			$expectedResults,
			$this->newUseCase( $validator, $searchEngine )
				->execute( new SimplePropertySearchRequest( $query, $language, $limit, $offset ) )
				->results
		);
	}

	public function testThrowsErrorOnInvalidLanguage(): void {
		$request = $this->createStub( SimplePropertySearchRequest::class );
		$expectedException = $this->createStub( UseCaseError::class );
		$validator = $this->createMock( SimplePropertySearchValidator::class );
		$validator->expects( $this->once() )
			->method( 'validate' )
			->with( $request )
			->willThrowException( $expectedException );

		$searchEngine = $this->createStub( PropertySearchEngine::class );

		try {
			$this->newUseCase( $validator, $searchEngine )->execute( $request );

			$this->fail( 'Expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase( SimplePropertySearchValidator $validator, PropertySearchEngine $searchEngine ): SimplePropertySearch {
		return new SimplePropertySearch( $validator, $searchEngine );
	}
}
