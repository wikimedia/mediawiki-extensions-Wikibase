<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Application\UseCases\ItemPrefixSearch;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearchRequest;
use Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearchValidator;
use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Model\User;
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
		$resultLanguage = 'de';
		$disableLanguageFallback = false;
		$username = null;
		$expectedResults = $this->createStub( ItemSearchResults::class );

		$searchEngine = $this->createMock( ItemPrefixSearchEngine::class );
		$searchEngine->expects( $this->once() )
			->method( 'suggestItems' )
			->with( $query, $language, $limit, $offset, $disableLanguageFallback, $resultLanguage, null )
			->willReturn( $expectedResults );

		$this->assertEquals(
			$expectedResults,
			$this->newUseCase(
				$this->createStub( ItemPrefixSearchValidator::class ),
				$searchEngine
			)->execute( new ItemPrefixSearchRequest(
				$query,
				$language,
				$limit,
				$offset,
				$username,
				$disableLanguageFallback,
				$resultLanguage,
				null
			) )->results
		);
	}

	public function testCanExecute_AndForwardsProfileContext(): void {
		$user = User::newAnonymous();
		$expectedResults = $this->createStub( ItemSearchResults::class );

		$searchEngine = $this->createMock( ItemPrefixSearchEngine::class );
		$searchEngine->expects( $this->once() )
			->method( 'suggestItems' )
			->with( 'potat', 'en', 10, 0, false, 'en', 'custom' )
			->willReturn( $expectedResults );

		$this->assertEquals(
			$expectedResults,
			$this->newUseCase(
				$this->createStub( ItemPrefixSearchValidator::class ),
				$searchEngine,
			)->execute( new ItemPrefixSearchRequest( 'potat', 'en', 10, 0, null, false, 'en', 'custom' ) )->results
		);
	}

	public function testValidatesTheRequest(): void {
		$request = $this->createStub( ItemPrefixSearchRequest::class );
		$expectedException = $this->createStub( UseCaseError::class );

		$validator = $this->createMock( ItemPrefixSearchValidator::class );
		$validator->expects( $this->once() )
			->method( 'validate' )
			->with( $request )
			->willThrowException( $expectedException );

		$user = User::newAnonymous();
		try {
			$this->newUseCase( $validator, $this->createStub( ItemPrefixSearchEngine::class ) )->execute( $request, $user );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(
		ItemPrefixSearchValidator $validator,
		ItemPrefixSearchEngine $searchEngine
	): ItemPrefixSearch {
		return new ItemPrefixSearch( $validator, $searchEngine );
	}
}
