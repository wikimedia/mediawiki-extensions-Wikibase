<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Application\UseCases\ItemPrefixSearch;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearchRequest;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Services\ItemPrefixSearchEngine;

/**
 * @covers \Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearch
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemPrefixSearchTest extends TestCase {

	public function testCanExecute(): void {
		$query = 'potat';
		$language = 'en';
		$limit = 10;
		$offset = 0;
		$expectedResults = $this->createStub( ItemSearchResults::class );

		$searchEngine = $this->createMock( ItemPrefixSearchEngine::class );
		$searchEngine->expects( $this->once() )
			->method( 'suggestItems' )
			->with( $query, $language, $limit, $offset )
			->willReturn( $expectedResults );

		$this->assertEquals(
			$expectedResults,
			$this->newUseCase( $searchEngine )
				->execute( new ItemPrefixSearchRequest( $query, $language, $limit, $offset ) )
				->getResults()
		);
	}

	private function newUseCase( ItemPrefixSearchEngine $searchEngine ): ItemPrefixSearch {
		return new ItemPrefixSearch( $searchEngine );
	}
}
