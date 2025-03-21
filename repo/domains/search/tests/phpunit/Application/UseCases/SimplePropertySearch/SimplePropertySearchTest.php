<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Application\UseCases\SimplePropertySearch;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearchRequest;
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
		$expectedResults = $this->createStub( PropertySearchResults::class );

		$searchEngine = $this->createMock( PropertySearchEngine::class );
		$searchEngine->expects( $this->once() )
			->method( 'searchPropertyByLabel' )
			->with( $query, $language )
			->willReturn( $expectedResults );

		$this->assertEquals(
			$expectedResults,
			$this->newUseCase( $searchEngine )
				->execute( new SimplePropertySearchRequest( $query, $language ) )
				->getResults()
		);
	}

	private function newUseCase( PropertySearchEngine $searchEngine ): SimplePropertySearch {
		return new SimplePropertySearch( $searchEngine );
	}
}
