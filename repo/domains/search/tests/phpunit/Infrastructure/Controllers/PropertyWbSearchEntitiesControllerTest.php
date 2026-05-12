<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Infrastructure\Controllers;

use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearchValidator;
use Wikibase\Repo\Domains\Search\Application\Validation\SearchLanguageValidator;
use Wikibase\Repo\Domains\Search\Domain\Model\Description;
use Wikibase\Repo\Domains\Search\Domain\Model\Label;
use Wikibase\Repo\Domains\Search\Domain\Model\MatchedData;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResults;
use Wikibase\Repo\Domains\Search\Domain\Services\PropertyPrefixSearchEngine;
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
		$searchResult = new PropertySearchResult(
			$propertyId,
			new Label( 'en', 'instance of' ),
			new Description( 'en', 'type to which this subject belongs' ),
			new MatchedData( 'label', 'en', 'instance of' )
		);

		$controller = $this->newController( new PropertySearchResults( $searchResult ) );
		$results = $controller->search( new WbSearchEntitiesRequest( 'instance', 'en', 'en', 5, false, null ) );

		$this->assertCount( 1, $results );
		$this->assertEquals(
			new TermSearchResult(
				new Term( 'en', 'instance of' ),
				'label',
				$propertyId,
				new Term( 'en', 'instance of' ),
				new Term( 'en', 'type to which this subject belongs' ),
				[ TermSearchResult::CONCEPTURI_META_DATA_KEY => 'http://www.wikidata.org/entity/P42' ]
			),
			$results[0]
		);
	}

	public function testNullLabelAndDescription(): void {
		$searchResult = new PropertySearchResult(
			new NumericPropertyId( 'P1' ),
			null,
			null,
			new MatchedData( 'label', 'en', 'test' )
		);

		$controller = $this->newController( new PropertySearchResults( $searchResult ) );
		$results = $controller->search( new WbSearchEntitiesRequest( 'test', 'en', 'en', 5, false, null ) );

		$this->assertCount( 1, $results );
		$this->assertNull( $results[0]->getDisplayLabel() );
		$this->assertNull( $results[0]->getDisplayDescription() );
	}

	public function testEntityIdMatch(): void {
		$searchResult = new PropertySearchResult(
			new NumericPropertyId( 'P42' ),
			null,
			null,
			new MatchedData( 'entityId', null, 'P42' )
		);

		$controller = $this->newController( new PropertySearchResults( $searchResult ) );
		$results = $controller->search( new WbSearchEntitiesRequest( 'P42', 'en', 'en', 5, false, null ) );

		$this->assertCount( 1, $results );
		$this->assertSame( 'pid', $results[0]->getMatchedTerm()->getLanguageCode() );
		$this->assertSame( 'P42', $results[0]->getMatchedTerm()->getText() );
	}

	public function testEmptyResults(): void {
		$controller = $this->newController( new PropertySearchResults() );
		$results = $controller->search( new WbSearchEntitiesRequest( 'foo', 'en', 'en', 5, false, null ) );

		$this->assertSame( [], $results );
	}

	private function newController( PropertySearchResults $searchResults ): PropertyWbSearchEntitiesController {
		$searchEngine = $this->createStub( PropertyPrefixSearchEngine::class );
		$searchEngine->method( 'suggestProperties' )->willReturn( $searchResults );

		$entitySource = $this->createStub( EntitySource::class );
		$entitySource->method( 'getConceptBaseUri' )->willReturn( 'http://www.wikidata.org/entity/' );

		$entitySourceLookup = $this->createStub( EntitySourceLookup::class );
		$entitySourceLookup->method( 'getEntitySourceById' )->willReturn( $entitySource );

		return new PropertyWbSearchEntitiesController(
			new PropertyPrefixSearch(
				new PropertyPrefixSearchValidator( $this->newAllowingLanguageValidator() ),
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
