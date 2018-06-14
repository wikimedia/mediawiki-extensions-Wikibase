<?php

namespace Wikibase\Repo\Tests\Api;

use ApiMain;
use CirrusSearch;
use FauxRequest;
use Language;
use MediaWikiTestCase;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Interactors\ConfigurableTermSearchInteractor;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Api\CombinedEntitySearchHelper;
use Wikibase\Repo\Api\EntityIdSearchHelper;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\Api\EntityTermSearchHelper;
use Wikibase\Repo\Api\SearchEntities;
use Wikibase\Repo\Search\Elastic\EntitySearchElastic;

/**
 * @covers \Wikibase\Repo\Api\EntityTermSearchHelper
 * @covers \Wikibase\Repo\Api\SearchEntities
 * @covers \Wikibase\Repo\Search\Elastic\EntitySearchElastic
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class SearchEntitiesIntegrationTest extends MediaWikiTestCase {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	protected function setUp() {
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
					$this->getMock( LabelDescriptionLookup::class ),
					[]
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
	 * @dataProvider provideQueriesForEntityIds
	 */
	public function testElasticSearchIntegration( $query, array $expectedIds ) {
		if ( !class_exists( CirrusSearch::class ) ) {
			$this->markTestSkipped( 'CirrusSearch needed.' );
		}

		$mockEntitySearchElastic = $this->getMockBuilder( EntitySearchElastic::class )
				->disableOriginalConstructor()
				->setMethods( [ 'getRankedSearchResults' ] )
				->getMock();

		$mockEntitySearchElastic->method( 'getRankedSearchResults' )
			->willReturnCallback( $this->makeElasticSearchCallback() );

		$resultData = $this->executeApiModule( $mockEntitySearchElastic, $query );
		$this->assertSameSearchResults( $resultData, $expectedIds );
	}

	/**
	 * Create callback that transforms JSON query return to TermSearchResult[]
	 *
	 * @return \Closure
	 */
	private function makeElasticSearchCallback() {
		$entitySearchElastic = $this->newEntitySearchElastic();

		return function ( $text, $languageCode, $entityType, $limit, $strictLanguage )
				use ( $entitySearchElastic ) {
			$result = $entitySearchElastic->getRankedSearchResults(
				$text,
				$languageCode,
				$entityType,
				$limit,
				$strictLanguage
			);
			// comes out as JSON data
			$resultData = json_decode( $result, true );
			// FIXME: this is very brittle, but I don't know how to make it better.
			$matchId = $resultData['query']['query']['bool']['should'][1]['term']['title.keyword'];
			try {
				$entityId = $this->idParser->parse( $matchId );
			} catch ( EntityIdParsingException $ex ) {
				return [];
			}

			return [ new TermSearchResult( new Term( $languageCode, $matchId ), '', $entityId ) ];
		};
	}

	/**
	 * @return EntitySearchElastic
	 */
	private function newEntitySearchElastic() {
		global $wgWBRepoSettings;

		$entitySearchElastic = new EntitySearchElastic(
			$this->newLanguageFallbackChainFactory(),
			$this->idParser,
			$this->getMockBuilder( Language::class )->disableOriginalConstructor()->getMock(),
			[ 'item' => 'wikibase-item' ],
			$wgWBRepoSettings['entitySearch'],
			new FauxRequest(),
			CirrusSearch\CirrusDebugOptions::forDumpingQueriesInUnitTests()
		);

		return $entitySearchElastic;
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

		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P1' ), '' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P2' ), '' );

		$apiModule = new SearchEntities(
			new ApiMain( $context ),
			'',
			$entitySearchTermIndex,
			$this->newEntityTitleLookup(),
			$dataTypeLookup,
			new StaticContentLanguages( [ 'en' ] ),
			[ 'item', 'property' ],
			[ '' => 'conceptBaseUri:' ]
		);

		$apiModule->execute();

		return $apiModule->getResult()->getResultData( null, [ 'Strip' => 'all' ] );
	}

	/**
	 * @return ConfigurableTermSearchInteractor
	 */
	private function newConfigurableTermSearchInteractor() {
		$interactor = $this->getMock( ConfigurableTermSearchInteractor::class );
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
		$lookup = $this->getMock( EntityLookup::class );
		$lookup->method( 'hasEntity' )->willReturn( true );

		return $lookup;
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function newEntityTitleLookup() {
		$lookup = $this->getMock( EntityTitleLookup::class );
		$lookup->method( 'getTitleForId' )->willReturn( $this->getMock( Title::class ) );

		return $lookup;
	}

	/**
	 * @return LanguageFallbackChainFactory
	 */
	private function newLanguageFallbackChainFactory() {
		$fallbackChain = new LanguageFallbackChain( [] );

		$factory = $this->getMock( LanguageFallbackChainFactory::class );
		$factory->method( 'newFromLanguage' )->willReturn( $fallbackChain );
		$factory->method( 'newFromLanguageCode' )->willReturn( $fallbackChain );

		return $factory;
	}

}
