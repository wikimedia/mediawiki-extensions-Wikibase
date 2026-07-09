<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Infrastructure\Controllers;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\EntitySearchException;
use Wikibase\Repo\Api\PropertyDataTypeSearchHelper;
use Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearchValidator;
use Wikibase\Repo\Domains\Search\Application\Validation\SearchLanguageValidator;
use Wikibase\Repo\Domains\Search\Application\Validation\ValidationError;
use Wikibase\Repo\Domains\Search\Domain\Model\Description;
use Wikibase\Repo\Domains\Search\Domain\Model\Label;
use Wikibase\Repo\Domains\Search\Domain\Model\MatchedData;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertyPrefixSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertyPrefixSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Model\User;
use Wikibase\Repo\Domains\Search\Domain\Services\PermissionChecker;
use Wikibase\Repo\Domains\Search\Domain\Services\PropertyPrefixSearchEngine;
use Wikibase\Repo\Domains\Search\Infrastructure\Controllers\PropertyConceptUriBuilder;
use Wikibase\Repo\Domains\Search\Infrastructure\Controllers\PropertyWbSearchEntitiesController;
use Wikibase\Repo\Domains\Search\Infrastructure\Controllers\WbSearchEntitiesRequest;

/**
 * @covers \Wikibase\Repo\Domains\Search\Infrastructure\Controllers\PropertyWbSearchEntitiesController
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyWbSearchEntitiesControllerTest extends TestCase {

	public function testSearchWithFullResult(): void {
		$propertyId = new NumericPropertyId( 'P42' );
		$searchResult = new PropertyPrefixSearchResult(
			$propertyId,
			new Label( 'en', 'instance of' ),
			new Description( 'en', 'type to which this subject belongs' ),
			new MatchedData( 'label', 'en', 'instance of' ),
			'wikibase-item',
		);

		$searchEngine = $this->createMock( PropertyPrefixSearchEngine::class );
		$searchEngine->expects( $this->once() )
			->method( 'suggestProperties' )
			->with( 'instance', 'en', 5, 3, false, 'en' )
			->willReturn( new PropertyPrefixSearchResults( $searchResult ) );

		$response = $this->newControllerWithEngine( $searchEngine )
			->search( new WbSearchEntitiesRequest( 'instance', 'en', 'en', 5, false, null, User::newAnonymous(), 3 ) );

		$this->assertCount( 1, $response->results );
		$this->assertFalse( $response->hasMore );
		$this->assertEquals(
			new TermSearchResult(
				new Term( 'en', 'instance of' ),
				'label',
				$propertyId,
				new Term( 'en', 'instance of' ),
				new Term( 'en', 'type to which this subject belongs' ),
				[
					TermSearchResult::CONCEPTURI_META_DATA_KEY => 'http://www.wikidata.org/entity/P42',
					PropertyDataTypeSearchHelper::DATATYPE_META_DATA_KEY => 'wikibase-item',
				]
			),
			$response->results[0]
		);
	}

	public function testNullLabelAndDescription(): void {
		$searchResult = new PropertyPrefixSearchResult(
			new NumericPropertyId( 'P1' ),
			null,
			null,
			new MatchedData( 'label', 'en', 'test' ),
			'string',
		);

		$controller = $this->newController( new PropertyPrefixSearchResults( $searchResult ) );
		$response = $controller->search( new WbSearchEntitiesRequest( 'test', 'en', 'en', 5, false, null, User::newAnonymous() ) );

		$this->assertCount( 1, $response->results );
		$this->assertNull( $response->results[0]->getDisplayLabel() );
		$this->assertNull( $response->results[0]->getDisplayDescription() );
	}

	public function testEntityIdMatch(): void {
		$searchResult = new PropertyPrefixSearchResult(
			new NumericPropertyId( 'P42' ),
			null,
			null,
			new MatchedData( 'entityId', null, 'P42' ),
			'string',
		);

		$controller = $this->newController( new PropertyPrefixSearchResults( $searchResult ) );
		$response = $controller->search( new WbSearchEntitiesRequest( 'P42', 'en', 'en', 5, false, null, User::newAnonymous() ) );

		$this->assertCount( 1, $response->results );
		$this->assertSame( 'pid', $response->results[0]->getMatchedTerm()->getLanguageCode() );
		$this->assertSame( 'P42', $response->results[0]->getMatchedTerm()->getText() );
	}

	public function testInvalidLanguageThrowsEntitySearchException(): void {
		$rejectingValidator = $this->createStub( SearchLanguageValidator::class );
		$rejectingValidator->method( 'validate' )->willReturn(
			new ValidationError(
				SearchLanguageValidator::CODE_INVALID_LANGUAGE_CODE,
				[ SearchLanguageValidator::CONTEXT_LANGUAGE_CODE => 'xyz' ]
			)
		);

		$useCase = new PropertyPrefixSearch(
			new PropertyPrefixSearchValidator(
				$rejectingValidator,
				$this->createStub( PermissionChecker::class ),
				50,
				500
			),
			$this->createStub( PropertyPrefixSearchEngine::class )
		);

		$controller = new PropertyWbSearchEntitiesController(
			$useCase,
			$this->createStub( PropertyConceptUriBuilder::class )
		);

		$this->expectException( EntitySearchException::class );
		$controller->search( new WbSearchEntitiesRequest( 'test', 'xyz', 'xyz', 5, false, null, User::newAnonymous() ) );
	}

	public function testEmptyResults(): void {
		$controller = $this->newController( new PropertyPrefixSearchResults() );
		$response = $controller->search( new WbSearchEntitiesRequest( 'foo', 'en', 'en', 5, false, null, User::newAnonymous() ) );

		$this->assertSame( [], $response->results );
		$this->assertFalse( $response->hasMore );
	}

	public function testSurfacesHasMore(): void {
		$searchResult = new PropertyPrefixSearchResult(
			new NumericPropertyId( 'P42' ),
			null,
			null,
			new MatchedData( 'label', 'en', 'test' ),
			'string',
		);

		$controller = $this->newController( PropertyPrefixSearchResults::withHasMore( true, $searchResult ) );
		$response = $controller->search( new WbSearchEntitiesRequest( 'test', 'en', 'en', 5, false, null, User::newAnonymous() ) );

		$this->assertTrue( $response->hasMore );
	}

	private function newController( PropertyPrefixSearchResults $searchResults ): PropertyWbSearchEntitiesController {
		$searchEngine = $this->createStub( PropertyPrefixSearchEngine::class );
		$searchEngine->method( 'suggestProperties' )->willReturn( $searchResults );

		return $this->newControllerWithEngine( $searchEngine );
	}

	private function newControllerWithEngine( PropertyPrefixSearchEngine $searchEngine ): PropertyWbSearchEntitiesController {
		$propertyConceptUriBuilder = $this->createStub( PropertyConceptUriBuilder::class );
		$propertyConceptUriBuilder->method( 'buildConceptUri' )
			->willReturnCallback( fn( $id ) => 'http://www.wikidata.org/entity/' . $id->getSerialization() );

		return new PropertyWbSearchEntitiesController(
			new PropertyPrefixSearch(
				new PropertyPrefixSearchValidator(
					$this->newAllowingLanguageValidator(),
					$this->createStub( PermissionChecker::class ),
					50,
					500
				),
				$searchEngine
			),
			$propertyConceptUriBuilder
		);
	}

	private function newAllowingLanguageValidator(): SearchLanguageValidator {
		$validator = $this->createStub( SearchLanguageValidator::class );
		$validator->method( 'validate' )->willReturn( null );
		return $validator;
	}

}
