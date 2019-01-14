<?php

namespace Wikibase\Repo\Search\Elastic\Tests;

use CirrusSearch;
use CirrusSearch\CirrusDebugOptions;
use Language;
use MediaWikiTestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Repo\Search\Elastic\EntitySearchElastic;

/**
 * @covers \Wikibase\Repo\Search\Elastic\EntitySearchElastic
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class EntitySearchElasticTest extends MediaWikiTestCase {

	public function setUp() {
		parent::setUp();
		if ( !class_exists( CirrusSearch::class ) ) {
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
			$repo->getSettings()->getSetting( 'entitySearch' ),
			new \FauxRequest(),
			CirrusDebugOptions::forDumpingQueriesInUnitTests()
		);
	}

	public function searchDataProvider() {
		$tests = [];
		foreach ( glob( __DIR__ . '/../../../data/entitySearch/*.query' ) as $queryFile ) {
			$testName = substr( basename( $queryFile ), 0, -6 );
			$query = json_decode( file_get_contents( $queryFile ), true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				throw new \RuntimeException( "invalid json: " . $queryFile );
			}
			$expectedFile = __DIR__ . "/../../../data/entitySearch/$testName-es" .
				EntitySearchElastic::getExpectedElasticMajorVersion() . '.expected';
			$tests[$testName] = [ $query, $expectedFile ];
		}

		return $tests;
	}

	private function overrideProfiles( array $profiles ) {
		$hookName = 'CirrusSearchProfileService';
		$handlers = $GLOBALS['wgHooks'][$hookName];
		$handlers[] = function ( $service ) use ( $profiles ) {
			foreach ( $profiles as $repoType => $contextProfiles ) {
				$service->registerArrayRepository( $repoType, 'phpunit_config', $contextProfiles );
			}
		};
		$this->mergeMWGlobalArrayValue( 'wgHooks', [ $hookName => $handlers ] );
	}

	private function resetGlobalSearchConfig() {
		// For whatever reason the mediawiki test suite reuses the same config
		// objects for the entire test. This breaks caches inside the cirrus
		// SearchConfig, so reset them as necessary.
		$config = \MediaWiki\MediaWikiServices::getInstance()
			->getConfigFactory()
			->makeConfig( 'CirrusSearch' );
		$reflProp = new \ReflectionProperty( $config, 'profileService');
		$reflProp->setAccessible( true );
		$reflProp->setValue( $config, null );
	}

	/**
	 * @dataProvider searchDataProvider
	 * @param string[] $params query parameters
	 * @param string $expected
	 */
	public function testSearchElastic( $params, $expected ) {
		$this->resetGlobalSearchConfig();
		if ( isset( $params['profiles'] ) ) {
			$this->overrideProfiles( $params['profiles'] );
		}
		$this->setMwGlobals( [ 'wgEntitySearchUseCirrus' => true ] );
		$search = $this->newEntitySearch( Language::factory( $params['userLang'] ) );
		$limit = 10;
		if ( isset( $params['limit'] ) ) {
			$limit = $params['limit'];
		}
		$elasticQuery = $search->getRankedSearchResults(
			$params['search'], $params['language'],
			$params['type'], $limit, $params['strictlanguage']
		);
		$decodedQuery = json_decode( $elasticQuery, true );
		unset( $decodedQuery['path'] );
		// serialize_precision set for T205958
		$this->setIniSetting( 'serialize_precision', 10 );
		$encodedData = json_encode( $decodedQuery, JSON_PRETTY_PRINT );
		$createIfMissing = getenv( 'WIKIBASE_CREATE_FIXTURES' ) === 'yes';
		$this->assertFileContains( $expected, $encodedData, $createIfMissing );
	}

}
