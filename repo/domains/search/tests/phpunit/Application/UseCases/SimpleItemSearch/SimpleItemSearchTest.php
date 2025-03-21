<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Application\UseCases\SimpleItemSearch;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearchRequest;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearchValidator;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Services\ItemSearchEngine;

/**
 * @covers \Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearch
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SimpleItemSearchTest extends TestCase {

	public function testCanExecute(): void {
		$query = 'needle';
		$language = 'en';
		$expectedResults = $this->createStub( ItemSearchResults::class );

		$searchEngine = $this->createMock( ItemSearchEngine::class );
		$searchEngine->expects( $this->once() )
			->method( 'searchItemByLabel' )
			->with( $query, $language )
			->willReturn( $expectedResults );

		$this->assertEquals(
			$expectedResults,
			$this->newUseCase( $searchEngine )
				->execute( new SimpleItemSearchRequest( $query, $language ) )
				->getResults()
		);
	}

	private function newUseCase( ItemSearchEngine $searchEngine ): SimpleItemSearch {
		return new SimpleItemSearch( $this->createStub( SimpleItemSearchValidator::class ), $searchEngine );
	}
}
