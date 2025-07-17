<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Application\UseCases\PropertyPrefixSearch;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearchRequest;
use Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearchValidator;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResults;
use Wikibase\Repo\Domains\Search\Domain\Services\PropertyPrefixSearchEngine;

/**
 * @covers \Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearch
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyPrefixSearchTest extends TestCase {

	public function testCanExecute(): void {
		$query = 'subcla';
		$language = 'en';
		$limit = 10;
		$offset = 0;
		$expectedResults = $this->createStub( PropertySearchResults::class );

		$searchEngine = $this->createMock( PropertyPrefixSearchEngine::class );
		$searchEngine->expects( $this->once() )
			->method( 'suggestProperties' )
			->with( $query, $language, $limit, $offset )
			->willReturn( $expectedResults );

		$this->assertEquals(
			$expectedResults,
			$this->newUseCase(
				$this->createStub( PropertyPrefixSearchValidator::class ),
				$searchEngine
			)->execute( new PropertyPrefixSearchRequest( $query, $language, $limit, $offset ) )
				->results
		);
	}

	public function testValidatesTheRequest(): void {
		$request = $this->createStub( PropertyPrefixSearchRequest::class );
		$expectedException = $this->createStub( UseCaseError::class );

		$validator = $this->createMock( PropertyPrefixSearchValidator::class );
		$validator->expects( $this->once() )
			->method( 'validate' )
			->with( $request )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase( $validator, $this->createStub( PropertyPrefixSearchEngine::class ) )->execute( $request );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(
		PropertyPrefixSearchValidator $validator,
		PropertyPrefixSearchEngine $searchEngine
	): PropertyPrefixSearch {
		return new PropertyPrefixSearch( $validator, $searchEngine );
	}
}
