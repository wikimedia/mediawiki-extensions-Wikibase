<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Infrastructure\Controllers;

use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\EntitySearchException;
use Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearchValidator;
use Wikibase\Repo\Domains\Search\Application\Validation\SearchLanguageValidator;
use Wikibase\Repo\Domains\Search\Application\Validation\ValidationError;
use Wikibase\Repo\Domains\Search\Domain\Model\Description;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Model\Label;
use Wikibase\Repo\Domains\Search\Domain\Model\MatchedData;
use Wikibase\Repo\Domains\Search\Domain\Services\ItemPrefixSearchEngine;
use Wikibase\Repo\Domains\Search\Domain\Services\PermissionChecker;
use Wikibase\Repo\Domains\Search\Infrastructure\Controllers\ItemWbSearchEntitiesController;
use Wikibase\Repo\Domains\Search\Infrastructure\Controllers\WbSearchEntitiesRequest;

/**
 * @covers \Wikibase\Repo\Domains\Search\Infrastructure\Controllers\ItemWbSearchEntitiesController
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemWbSearchEntitiesControllerTest extends TestCase {

	public function testSearchWithFullResult(): void {
		$itemId = new ItemId( 'Q42' );
		$searchResult = new ItemSearchResult(
			$itemId,
			new Label( 'en', 'Douglas Adams' ),
			new Description( 'en', 'Author' ),
			new MatchedData( 'label', 'en', 'Douglas Adams' )
		);

		$controller = $this->newController( new ItemSearchResults( $searchResult ) );
		$results = $controller->search( new WbSearchEntitiesRequest( 'Douglas', 'en', 'en', 5, false, null, null ) );

		$this->assertCount( 1, $results );
		$this->assertEquals(
			new TermSearchResult(
				new Term( 'en', 'Douglas Adams' ),
				'label',
				$itemId,
				new Term( 'en', 'Douglas Adams' ),
				new Term( 'en', 'Author' ),
				[ TermSearchResult::CONCEPTURI_META_DATA_KEY => 'http://www.wikidata.org/entity/Q42' ]
			),
			$results[0]
		);
	}

	public function testNullLabelAndDescription(): void {
		$searchResult = new ItemSearchResult(
			new ItemId( 'Q1' ),
			null,
			null,
			new MatchedData( 'label', 'en', 'test' )
		);

		$controller = $this->newController( new ItemSearchResults( $searchResult ) );
		$results = $controller->search( new WbSearchEntitiesRequest( 'test', 'en', 'en', 5, false, null, null ) );

		$this->assertCount( 1, $results );
		$this->assertNull( $results[0]->getDisplayLabel() );
		$this->assertNull( $results[0]->getDisplayDescription() );
	}

	public function testEntityIdMatch(): void {
		$searchResult = new ItemSearchResult(
			new ItemId( 'Q42' ),
			null,
			null,
			new MatchedData( 'entityId', null, 'Q42' )
		);

		$controller = $this->newController( new ItemSearchResults( $searchResult ) );
		$results = $controller->search( new WbSearchEntitiesRequest( 'Q42', 'en', 'en', 5, false, null, null ) );

		$this->assertCount( 1, $results );
		$this->assertSame( 'qid', $results[0]->getMatchedTerm()->getLanguageCode() );
		$this->assertSame( 'Q42', $results[0]->getMatchedTerm()->getText() );
	}

	public function testEmptyResults(): void {
		$controller = $this->newController( new ItemSearchResults() );
		$results = $controller->search( new WbSearchEntitiesRequest( 'foo', 'en', 'en', 5, false, null, null ) );

		$this->assertSame( [], $results );
	}

	public function testInvalidLanguageThrowsEntitySearchException(): void {
		$rejectingValidator = $this->createStub( SearchLanguageValidator::class );
		$rejectingValidator->method( 'validate' )->willReturn(
			new ValidationError(
				SearchLanguageValidator::CODE_INVALID_LANGUAGE_CODE,
				[ SearchLanguageValidator::CONTEXT_LANGUAGE_CODE => 'xyz' ]
			)
		);

		$useCase = new ItemPrefixSearch(
			new ItemPrefixSearchValidator( $rejectingValidator,
				$this->createStub( PermissionChecker::class ),
				50,
				500
			),
			$this->createStub( ItemPrefixSearchEngine::class )
		);

		$controller = new ItemWbSearchEntitiesController(
			$useCase,
			$this->createStub( EntitySourceLookup::class )
		);

		$this->expectException( EntitySearchException::class );
		$controller->search( new WbSearchEntitiesRequest( 'test', 'xyz', 'xyz', 5, false, null, null ) );
	}

	private function newController( ItemSearchResults $searchResults ): ItemWbSearchEntitiesController {
		$searchEngine = $this->createStub( ItemPrefixSearchEngine::class );
		$searchEngine->method( 'suggestItems' )->willReturn( $searchResults );

		$entitySource = $this->createStub( EntitySource::class );
		$entitySource->method( 'getConceptBaseUri' )->willReturn( 'http://www.wikidata.org/entity/' );

		$entitySourceLookup = $this->createStub( EntitySourceLookup::class );
		$entitySourceLookup->method( 'getEntitySourceById' )->willReturn( $entitySource );

		$permissionChecker = $this->createStub( PermissionChecker::class );

		return new ItemWbSearchEntitiesController(
			new ItemPrefixSearch(
				new ItemPrefixSearchValidator(
					$this->newAllowingLanguageValidator(),
					$permissionChecker,
					50,
					500
				),
				$searchEngine
			),
			$entitySourceLookup
		);
	}

	private function newAllowingLanguageValidator(): SearchLanguageValidator {
		$validator = $this->createStub( SearchLanguageValidator::class );
		$validator->method( 'validate' )->willReturn( null );
		return $validator;
	}

}
