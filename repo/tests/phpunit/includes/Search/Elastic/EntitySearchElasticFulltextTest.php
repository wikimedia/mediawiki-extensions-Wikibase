<?php

namespace Wikibase\Repo\Search\Elastic\Tests;

use CirrusSearch\Query\BoostTemplatesFeature;
use CirrusSearch\Query\FullTextQueryStringQueryBuilder;
use CirrusSearch\Query\SimpleInSourceFeature;
use CirrusSearch\Search\SearchContext;
use CirrusSearch\SearchConfig;
use Language;
use MediaWikiTestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Search\Elastic\EntityFullTextQueryBuilder;
use Wikibase\Repo\Search\Elastic\EntitySearchElastic;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SettingsArray;

/**
 * @covers  \Wikibase\Repo\Search\Elastic\EntitySearchElastic
 * @group Wikibase
 * @license GPL-2.0+
 * @author  Stas Malyshev
 */
class EntitySearchElasticFulltextTest extends MediaWikiTestCase {

	public function setUp() {
		parent::setUp();
		if ( !class_exists( 'CirrusSearch' ) ) {
			$this->markTestSkipped( 'CirrusSearch not installed, skipping' );
		}
	}

	/**
	 * @param Language $userLang
	 * @return EntitySearchElastic
	 */
	private function newEntitySearch( Language $userLang ) {
		$repo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();

		return new EntitySearchElastic(
			$repo->getLanguageFallbackChainFactory(),
			new BasicEntityIdParser(),
			$userLang,
			$repo->getContentModelMappings(),
			$repo->getSettings()->getSetting( 'entitySearch' )
		);
	}

	/**
	 * @return \FauxRequest
	 */
	private function getMockRequest() {
		return new \FauxRequest( [ 'cirrusDumpQuery' => 'yes' ] );
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

	private function getMockRepo( $lang, $realSettings ) {
		$repo = $this->getMockBuilder( WikibaseRepo::class )->disableOriginalConstructor()->getMock();

		$mockSettings = $this->getMock( SettingsArray::class );
		$repo->method( 'getSettings' )->willReturn( $mockSettings );
		$mockSettings->method( 'getSetting' )->with( 'entitySearch' )->willReturn( $realSettings );

		$mockLookup = $this->getMockBuilder( EntityNamespaceLookup::class )
			->disableOriginalConstructor()
			->getMock();
		$mockLookup->method( 'isEntityNamespace' )->willReturnCallback( function ( $ns ) {
			return $ns < 10;
		} );
		$repo->method( 'getEntityNamespaceLookup' )->willReturn( $mockLookup );

		$repo->method( 'getUserLanguage' )->willReturn( Language::factory( $lang ) );
		$repo->method( 'getLanguageFallbackChainFactory' )
			->willReturn( new LanguageFallbackChainFactory() );

		$repo->method( 'getEntityIdParser' )->willReturn( new ItemIdParser() );

		return $repo;
	}

	/**
	 * @dataProvider searchDataProvider
	 * @param string[] $params
	 * @param string $expected
	 */
	public function testSearchElastic( $params, $expected ) {

		$wgSettings['statementBoost'] = [ 'P31=Q4167410' => '-10' ];
		$wgSettings['defaultPrefixRescoreProfile'] = 'wikibase_prefix_boost';
		$wgSettings['prefixRescoreProfile'] = 'wikibase_prefix_boost';
		$wgSettings['useStemming'] = [ 'en' => [ 'query' => true ] ];
		$wgSettings['searchProfiles'] =
			require __DIR__ . '/../../../../../config/EntitySearchProfiles.php';
		$wgSettings['rescoreProfiles'] =
			require __DIR__ . '/../../../../../config/ElasticSearchRescoreProfiles.php';
		$wgSettings['originalSearchProfile'] = 'default';
		$wgSettings['fulltextSearchProfile'] = 'wikibase';

		$this->setMwGlobals( [
			'wgCirrusSearchRescoreProfiles' =>
				include __DIR__ . '/../../../../../config/ElasticSearchRescoreProfiles.php',
			'wgCirrusSearchRescoreFunctionScoreChains' =>
				include __DIR__ . '/../../../../../config/ElasticSearchRescoreFunctions.php',
			'wgCirrusSearchFullTextQueryBuilderProfiles' => [
				'default' => [
					'builder_class' => FullTextQueryStringQueryBuilder::class,
					'settings' => [],
				],
			],
			'wgCirrusSearchRescoreProfile' => 'wikibase',
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
		$repo = $this->getMockRepo( $params['userLang'], $wgSettings );
		$features = [
			new SimpleInSourceFeature(),
			new BoostTemplatesFeature(),
		];
		$builder = new EntityFullTextQueryBuilder( $config, $features, $settings, $repo );

		$context = new SearchContext( $config, $params['ns'] );
		$builder->build( $context, $params['search'], false );
		$query = $context->getQuery();
		$context->getRescore();
		$encoded = json_encode( $query->toArray(), JSON_PRETTY_PRINT );
		$this->assertFileContains( $expected, $encoded );
	}

}
