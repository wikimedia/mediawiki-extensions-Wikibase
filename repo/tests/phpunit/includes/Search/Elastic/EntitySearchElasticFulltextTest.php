<?php

namespace Wikibase\Repo\Search\Elastic\Tests;

use CirrusSearch\Profile\SearchProfileService;
use CirrusSearch\Query\BoostTemplatesFeature;
use CirrusSearch\Query\FullTextQueryBuilder;
use CirrusSearch\Query\FullTextQueryStringQueryBuilder;
use CirrusSearch\Query\InSourceFeature;
use CirrusSearch\Search\SearchContext;
use CirrusSearch\SearchConfig;
use MediaWikiTestCase;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Search\Elastic\EntityFullTextQueryBuilder;
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
	private static $ENTITY_SEARH_CONFIG = [
		'statementBoost' => [ 'P31=Q4167410' => '-10' ],
		'defaultFulltextRescoreProfile' => 'wikibase_unittest',
		'useStemming' => [ 'en' => [ 'query' => true ] ],
		'rescoreProfiles' => [
			'wikibase_unittest' => [
				'i18n_msg' => 'wikibase-rescore-profile-fulltext',
				'supported_namespaces' => 'all',
				'rescore' => [
					[
						'window' => 8192,
						'window_size_override' => 'EntitySearchRescoreWindowSize',
						'query_weight' => 1.0,
						'rescore_query_weight' => 1.0,
						'score_mode' => 'total',
						'type' => 'function_score',
						'function_chain' => 'entity_weight_unittest'
					],
				]
			]
		],
		'rescoreFunctionChains' => [
			'entity_weight_unittest' => [
				'score_mode' => 'sum',
				'functions' => [
					[
						// Incoming links: k = 100, since it is normal to have a bunch of incoming links
						'type' => 'satu',
						'weight' => '0.6',
						'params' => [ 'field' => 'incoming_links', 'missing' => 0, 'a' => 1 , 'k' => 100 ]
					],
					[
						// Site links: k = 20, tens of sites is a lot
						'type' => 'satu',
						'weight' => '0.4',
						'params' => [ 'field' => 'sitelink_count', 'missing' => 0, 'a' => 2, 'k' => 20 ]
					],
					[
						// (De)boosting by statement values
						'type' => 'term_boost',
						'weight' => 0.1,
						'params' => [
							'statement_keywords' => [
								// Q4167410=Wikimedia disambiguation page
								'P31=Q4167410' => -10,
							]
						]
					]
				],
			]
		]
	];

	public function setUp() {
		parent::setUp();
		if ( !class_exists( 'CirrusSearch' ) ) {
			$this->markTestSkipped( 'CirrusSearch not installed, skipping' );
		}

		// Override the profile service hooks so that we can test that the rescore profiles
		// are properly initialized
		parent::setTemporaryHook( 'CirrusSearchProfileService', function( SearchProfileService $service ) {
			RepoHooks::registerSearchProfiles( $service, self::$ENTITY_SEARH_CONFIG );
		} );
	}

	public function searchDataProvider() {
		$tests = [];
		foreach ( glob( __DIR__ . '/../../../data/entityFulltext/*.query' ) as $queryFile ) {
			$testName = substr( basename( $queryFile ), 0, - 6 );
			$query = json_decode( file_get_contents( $queryFile ), true );
			$expectedFile = substr( $queryFile, 0, - 5 ) . 'expected';
			$tests[$testName] = [ $query, $expectedFile ];
		}

		return $tests;
	}

	/**
	 * @return EntityNamespaceLookup
	 */
	private function getMockEntityNamespaceLookup() {
		$mockLookup = $this->getMockBuilder( EntityNamespaceLookup::class )
			->disableOriginalConstructor()
			->getMock();
		$mockLookup->method( 'isEntityNamespace' )->willReturnCallback( function ( $ns ) {
			return $ns < 10;
		} );
		return $mockLookup;
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
		$settings = [
			'any'               => 0.04,
			'lang-exact'        => 0.78,
			'lang-folded'       => 0.01,
			'lang-partial'      => 0.07,
			'fallback-exact'    => 0.38,
			'fallback-folded'   => 0.005,
			'fallback-partial'  => 0.03,
			'fallback-discount' => 0.1,
		];
		$features = [
			new InSourceFeature( $config ),
			new BoostTemplatesFeature(),
		];
		$builderSettings = $config->getProfileService()
			->loadProfileByName( SearchProfileService::FT_QUERY_BUILDER, 'default' );
		$delegate = new FullTextQueryStringQueryBuilder( $config, $features, $builderSettings['settings'] );

		$builder = new EntityFullTextQueryBuilder(
			$delegate,
			self::$ENTITY_SEARH_CONFIG,
			$settings,
			$this->getMockEntityNamespaceLookup(),
			new LanguageFallbackChainFactory(),
			new ItemIdParser(),
			$params['userLang']
		);

		$context = new SearchContext( $config, $params['ns'] );
		$builder->build( $context, $params['search'], false );
		$query = $context->getQuery();
		$rescore = $context->getRescore();
		$encoded = json_encode( [ 'query' => $query->toArray(), 'rescore_query' => $rescore ], JSON_PRETTY_PRINT );
		$this->assertFileContains( $expected, $encoded );
	}

	/**
	 * Check that other namespace searches go to delegate.
	 */
	public function testSearchDelegate() {
		$delegate = $this->getMock( FullTextQueryBuilder::class );
		$delegate->expects( $this->once() )->method( 'build' )->willReturn( false );
		$builder = new EntityFullTextQueryBuilder(
			$delegate,
			[],
			[],
			$this->getMockEntityNamespaceLookup(),
			new LanguageFallbackChainFactory(),
			new ItemIdParser(),
			'en'
		);

		$context = new SearchContext( new SearchConfig(), [ 150 ] );
		$builder->build( $context, "test", false );
	}

}
