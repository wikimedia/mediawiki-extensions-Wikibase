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

	private SimpleItemSearchValidator $validator;
	private ItemSearchEngine $searchEngine;

	protected function setUp(): void {
		parent::setUp();
		$this->validator = $this->createStub( SimpleItemSearchValidator::class );
		$this->searchEngine = $this->createStub( ItemSearchEngine::class );
	}

	public function testCanConstruct(): void {
		$this->assertInstanceOf( SimpleItemSearch::class, $this->newUseCase() );
	}

	public function testCanExecute(): void {
		$query = 'needle';
		$language = 'en';
		$expectedResults = $this->createStub( ItemSearchResults::class );

		$this->searchEngine = $this->createMock( ItemSearchEngine::class );
		$this->searchEngine->expects( $this->once() )
			->method( 'searchItemByLabel' )
			->with( $query, $language )
			->willReturn( $expectedResults );

		$this->assertEquals(
			$expectedResults,
			$this->newUseCase()
				->execute( new SimpleItemSearchRequest( $query, $language ) )
				->getResults()
		);
	}

	private function newUseCase(): SimpleItemSearch {
		return new SimpleItemSearch( $this->validator, $this->searchEngine );
	}
}
