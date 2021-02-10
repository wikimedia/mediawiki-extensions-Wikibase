<?php

namespace Wikibase\Repo\Tests\Api;

use ApiMain;
use FauxRequest;
use MediaWikiIntegrationTestCase;
use RequestContext;
use Title;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Interactors\ConfigurableTermSearchInteractor;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\EntityArticleIdLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\CombinedEntitySearchHelper;
use Wikibase\Repo\Api\EntityIdSearchHelper;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\Api\EntityTermSearchHelper;
use Wikibase\Repo\Api\SearchEntities;

/**
 * @covers \Wikibase\Repo\Api\EntityTermSearchHelper
 * @covers \Wikibase\Repo\Api\SearchEntities
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class SearchEntitiesIntegrationTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	protected function setUp(): void {
		parent::setUp();

		$this->idParser = new BasicEntityIdParser();
	}

	public function provideQueriesForEntityIds() {
		return [
			'Exact item ID' => [
				'Q1',
				[ 'Q1' ]
			],
			'Lower case item ID' => [
				'q2',
				[ 'Q2' ]
			],

			'Exact property ID' => [
				'P1',
				[ 'P1' ]
			],
			'Lower case property ID' => [
				'p2',
				[ 'P2' ]
			],

			'Copy paste with brackets' => [
				'(Q3)',
				[ 'Q3' ]
			],
			'Copy pasted concept URI' => [
				'http://www.wikidata.org/entity/Q4',
				[ 'Q4' ]
			],
			'Copy pasted page URL' => [
				'https://www.wikidata.org/wiki/Q5',
				[ 'Q5' ]
			],
		];
	}

	/**
	 * @dataProvider provideQueriesForEntityIds
	 */
	public function testTermTableIntegration( $query, array $expectedIds ) {
		$searchHelper = new CombinedEntitySearchHelper(
			[
				new EntityIdSearchHelper(
					$this->newEntityLookup(),
					$this->idParser,
					$this->createMock( LabelDescriptionLookup::class ),
					[ 'item' => [ '' ] ]
				),
				new EntityTermSearchHelper(
					$this->newConfigurableTermSearchInteractor()
				)
			]
		);

		$resultData = $this->executeApiModule( $searchHelper, $query );
		$this->assertSameSearchResults( $resultData, $expectedIds );
	}

	/**
	 * @param array[] $resultData
	 */
	private function assertSameSearchResults( array $resultData, array $expectedIds ) {
		$this->assertCount( count( $expectedIds ), $resultData['search'] );

		foreach ( $expectedIds as $index => $expectedId ) {
			$this->assertSame( $expectedId, $resultData['search'][$index]['id'] );
		}
	}

	/**
	 * @param EntitySearchHelper $entitySearchTermIndex
	 * @param string $query
	 *
	 * @return array
	 */
	private function executeApiModule( EntitySearchHelper $entitySearchTermIndex, $query ) {
		$context = new RequestContext();
		$context->setRequest( new FauxRequest( [
			'language' => 'en',
			'search' => $query,
		] ) );

		$apiModule = new SearchEntities(
			new ApiMain( $context ),
			'',
			$entitySearchTermIndex,
			$this->newEntityTitleLookup(),
			new StaticContentLanguages( [ 'en' ] ),
			new EntitySourceDefinitions( [
				new EntitySource(
					'test',
					'testdb',
					[
						'item' => [ 'namespaceId' => 123, 'slot' => 'main' ],
						'property' => [ 'namespaceId' => 321, 'slot' => 'main' ],
					],
					'conceptBaseUri:',
					'',
					'',
					''
				)
			], new EntityTypeDefinitions( [] ) ),
			$this->createMock( EntityTitleTextLookup::class ),
			$this->createMock( EntityUrlLookup::class ),
			$this->createMock( EntityArticleIdLookup::class ),
			$this->createMock( ApiErrorReporter::class )
		);

		$apiModule->execute();

		return $apiModule->getResult()->getResultData( null, [ 'Strip' => 'all' ] );
	}

	/**
	 * @return ConfigurableTermSearchInteractor
	 */
	private function newConfigurableTermSearchInteractor() {
		$interactor = $this->createMock( ConfigurableTermSearchInteractor::class );
		$interactor->method( 'searchForEntities' )->willReturnCallback(
			function ( $text, $languageCode, $entityType, array $termTypes ) {
				try {
					$entityId = $this->idParser->parse( $text );
				} catch ( EntityIdParsingException $ex ) {
					return [];
				}

				return [ new TermSearchResult( new Term( $languageCode, $text ), '', $entityId ) ];
			}
		);

		return $interactor;
	}

	/**
	 * @return EntityLookup
	 */
	private function newEntityLookup() {
		$lookup = $this->createMock( EntityLookup::class );
		$lookup->method( 'hasEntity' )->willReturn( true );

		return $lookup;
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function newEntityTitleLookup() {
		$titleLookup = $this->createMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->any() )
			->method( 'getTitlesForIds' )
			->will( $this->returnCallback( function ( $ids ) {
				$titles = [];
				/** @var EntityId $id */
				foreach ( $ids as $id ) {
					$titles[ $id->getSerialization() ] = $this->createMock( Title::class );
				}
				return $titles;
			} ) );

		return $titleLookup;
	}

}
