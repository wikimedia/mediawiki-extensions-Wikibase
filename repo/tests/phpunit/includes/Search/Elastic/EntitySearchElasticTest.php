<?php

namespace Wikibase\Repo\Search\Elastic\Tests;

use Language;
use MediaWikiTestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Repo\Search\Elastic\EntitySearchElastic;

/**
 * @group Wikibase
 * @covers EntitySearchElastic
 */
class EntitySearchElasticTest extends MediaWikiTestCase {

	public function setUp() {
		parent::setUp();
		if ( !class_exists( 'CirrusSearch' ) ) {
			$this->markTestSkipped( 'CirrusSearch not installed, skipping' );
		}
	}

	private function newEntitySearch( Language $userLang ) {
		$repo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();

		return new EntitySearchElastic(
			$repo->getLanguageFallbackChainFactory(),
			new BasicEntityIdParser(),
			$userLang,
			$repo->getContentModelMappings(),
			$this->getMockRequest(),
			$repo->getSettings()->getSetting( 'entitySearch' )
		);
	}

	private function getMockRequest() {
		return new \FauxRequest( [ 'cirrusDumpQuery' => 'yes', 'cirrusReturnResult' => 'yes' ] );
	}

	public function searchDataProvider() {
		$tests = [];
		foreach ( glob( __DIR__ . '/../../../data/entitySearch/*.query' ) as $queryFile ) {
			$testName = substr( basename( $queryFile ), 0, -6 );
			$query = json_decode( file_get_contents( $queryFile ), true );
			$expectedFile = substr( $queryFile, 0, -5 ) . 'expected';
			$expected =
				is_file( $expectedFile ) ? json_decode( file_get_contents( $expectedFile ), true )
					// Flags test to generate a new fixture
					: $expectedFile;
			$tests[$testName] = [
				$query,
				$expected,
			];
		}

		return $tests;
	}

	/**
	 * @dataProvider searchDataProvider
	 * @param $params
	 * @param $expected
	 */
	public function testSearchElastic( $params, $expected ) {
		$this->setMwGlobals( [
			'wgEntitySearchUseCirrus' => true,
			'wgCirrusSearchRescoreProfiles' =>
				include __DIR__ . '/../../../../../config/ElasticSearchRescoreProfiles.php',
			'wgCirrusSearchRescoreFunctionScoreChains' =>
				include __DIR__ . '/../../../../../config/ElasticSearchRescoreFunctions.php',
		] );
		$search = $this->newEntitySearch( Language::factory( $params['userLang'] ) );
		$elasticQuery = $search->getRankedSearchResults(
			$params['search'], $params['language'],
			$params['type'], 10, $params['strictlanguage']
		);

		if ( is_string( $expected ) ) {
			// Flag to generate a new fixture.
			$encodedQuery = json_encode( $elasticQuery['query'], JSON_PRETTY_PRINT );
			file_put_contents( $expected, $encodedQuery );
		} else {
			// Finally compare some things
			$this->assertEquals( $expected, $elasticQuery['query'] );
		}
	}

}
