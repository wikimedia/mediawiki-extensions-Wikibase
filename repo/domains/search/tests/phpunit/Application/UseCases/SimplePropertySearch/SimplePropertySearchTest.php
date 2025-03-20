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

	private PropertySearchEngine $searchEngine;

	protected function setUp(): void {
		parent::setUp();
		$this->searchEngine = $this->createStub( PropertySearchEngine::class );
	}

	public function testCanConstruct(): void {
		$this->assertInstanceOf( SimplePropertySearch::class, $this->newUseCase() );
	}

	public function testCanExecute(): void {
		$query = 'needle';
		$language = 'en';
		$expectedResults = $this->createStub( PropertySearchResults::class );

		$this->searchEngine = $this->createMock( PropertySearchEngine::class );
		$this->searchEngine->expects( $this->once() )
			->method( 'searchPropertyByLabel' )
			->with( $query, $language )
			->willReturn( $expectedResults );

		$this->assertEquals(
			$expectedResults,
			$this->newUseCase()
				->execute( new SimplePropertySearchRequest( $query, $language ) )
				->getResults()
		);
	}

	private function newUseCase(): SimplePropertySearch {
		return new SimplePropertySearch( $this->searchEngine );
	}
}
