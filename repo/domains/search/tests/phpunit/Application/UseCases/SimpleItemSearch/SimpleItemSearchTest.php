<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Application\UseCases\SimpleItemSearch;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearchRequest;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearchValidator;
use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;
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
		$limit = 10;
		$offset = 0;
		$expectedResults = $this->createStub( ItemSearchResults::class );

		$searchEngine = $this->createMock( ItemSearchEngine::class );
		$searchEngine->expects( $this->once() )
			->method( 'searchItemByLabel' )
			->with( $query, $language, $limit, $offset )
			->willReturn( $expectedResults );

		$this->assertEquals(
			$expectedResults,
			$this->newUseCase( $this->createStub( SimpleItemSearchValidator::class ), $searchEngine )
				->execute( new SimpleItemSearchRequest( $query, $language, $limit, $offset ) )
				->results
		);
	}

	public function testValidatesTheRequest(): void {
		$request = $this->createStub( SimpleItemSearchRequest::class );
		$expectedException = $this->createStub( UseCaseError::class );

		$validator = $this->createMock( SimpleItemSearchValidator::class );
		$validator->expects( $this->once() )
			->method( 'validate' )
			->with( $request )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase( $validator, $this->createStub( ItemSearchEngine::class ) )->execute( $request );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase( SimpleItemSearchValidator $validator, ItemSearchEngine $searchEngine ): SimpleItemSearch {
		return new SimpleItemSearch( $validator, $searchEngine );
	}
}
