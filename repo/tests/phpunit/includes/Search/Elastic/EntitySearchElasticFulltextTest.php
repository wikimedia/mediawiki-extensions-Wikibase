<?php

namespace Wikibase\Repo\Search\Elastic\Tests;

use CirrusSearch\Profile\SearchProfileService;
use CirrusSearch\Query\BoostTemplatesFeature;
use CirrusSearch\Query\FullTextQueryStringQueryBuilder;
use CirrusSearch\Query\InSourceFeature;
use CirrusSearch\Search\SearchContext;
use CirrusSearch\SearchConfig;
use MediaWikiTestCase;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Repo\Search\Elastic\EntityFullTextQueryBuilder;
use Wikibase\Repo\Search\Elastic\EntitySearchElastic;
use Wikibase\RepoHooks;

/**
 * @covers \Wikibase\Repo\Search\Elastic\EntityFullTextQueryBuilder
 * @covers \Wikibase\RepoHooks::registerSearchProfiles()
 *
 * @group Wikibase
 * @license GPL-2.0+
 * @author  Stas Malyshev
 */
class EntitySearchElasticFulltextTest extends MediaWikiTestCase {
	/**
	 * @var array search settings for the test
	 */
	private static $ENTITY_SEARCH_CONFIG = [
		'statementBoost' => [ 'P31=Q4167410' => '-10' ],
		'defaultFulltextRescoreProfile' => 'wikibase_prefix_boost',
		'useStemming' => [ 'en' => [ 'query' => true ] ]
	];

	public function setUp() {
		parent::setUp();
		if ( !class_exists( 'CirrusSearch' ) ) {
			$this->markTestSkipped( 'CirrusSearch not installed, skipping' );
		}

		// Override the profile service hooks so that we can test that the rescore profiles
		// are properly initialized
		parent::setTemporaryHook( 'CirrusSearchProfileService', function( SearchProfileService $service ) {
			RepoHooks::registerSearchProfiles( $service, self::$ENTITY_SEARCH_CONFIG );
		} );
	}

	public function searchDataProvider() {
		$tests = [];
		foreach ( glob( __DIR__ . '/../../../data/entityFulltext/*.query' ) as $queryFile ) {
			$testName = substr( basename( $queryFile ), 0, - 6 );
			$query = json_decode( file_get_contents( $queryFile ), true );
			$expectedFile = "$testName-es" . EntitySearchElastic::getExpectedElasticMajorVersion() . '.expected';
			$tests[$testName] = [ $query, $expectedFile ];
		}

		return $tests;
	}

	private function getConfigSettings() {
		return [
			'any'               => 0.04,
			'lang-exact'        => 0.78,
			'lang-folded'       => 0.01,
			'lang-partial'      => 0.07,
			'fallback-exact'    => 0.38,
			'fallback-folded'   => 0.005,
			'fallback-partial'  => 0.03,
			'fallback-discount' => 0.1,
		];
	}

	/**
	 * @dataProvider searchDataProvider
	 * @param string[] $params
	 * @param string $expected
	 */
	public function testSearchElastic( $params, $expected ) {
		$this->setMwGlobals( [
			'wgCirrusSearchQueryStringMaxDeterminizedStates' => 500,
			'wgCirrusSearchElasticQuirks' => [],
		] );

		$config = new SearchConfig();

		$builder = new EntityFullTextQueryBuilder(
			self::$ENTITY_SEARCH_CONFIG,
			$this->getConfigSettings(),
			new LanguageFallbackChainFactory(),
			new ItemIdParser(),
			$params['userLang']
		);

		$features = [
			new InSourceFeature( $config ),
			new BoostTemplatesFeature(),
		];
		$builderSettings = $config->getProfileService()
					   ->loadProfileByName( SearchProfileService::FT_QUERY_BUILDER, 'default' );
		$defaultBuilder = new FullTextQueryStringQueryBuilder( $config, $features, $builderSettings['settings'] );

		$context = new SearchContext( $config, $params['ns'] );
		$defaultBuilder->build( $context, $params['search'] );
		$builder->build( $context, $params['search'] );
		$query = $context->getQuery();
		$rescore = $context->getRescore();
		$encoded = json_encode( [ 'query' => $query->toArray(), 'rescore_query' => $rescore ],
				JSON_PRETTY_PRINT );
		$this->assertFileContains( $expected, $encoded );
	}

	/**
	 * Check that the search does not do anything if results are not possible
	 * or if advanced syntax is used.
	 */
	public function testSearchFallback() {
		$builder = new EntityFullTextQueryBuilder(
			[],
			[],
			new LanguageFallbackChainFactory(),
			new ItemIdParser(),
			'en'
		);

		$context = new SearchContext( new SearchConfig(), [ 150 ] );
		$context->setResultsPossible( false );

		$builder->build( $context, "test" );
		$this->assertNotContains( 'entity_full_text', $context->getSyntaxUsed() );

		$context->setResultsPossible( true );
		$context->addSyntaxUsed( 'regex' );

		$builder->build( $context, "test" );
		$this->assertNotContains( 'entity_full_text', $context->getSyntaxUsed() );
	}

}
