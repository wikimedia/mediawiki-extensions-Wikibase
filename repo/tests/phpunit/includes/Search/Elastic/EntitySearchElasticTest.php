<?php

namespace Wikibase\Repo\Search\Elastic\Tests;

use Language;
use MediaWikiTestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Repo\Search\Elastic\EntitySearchElastic;

/**
 * @covers \Wikibase\Repo\Search\Elastic\EntitySearchElastic
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Stas Malyshev
 */
class EntitySearchElasticTest extends MediaWikiTestCase {

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
		foreach ( glob( __DIR__ . '/../../../data/entitySearch/*.query' ) as $queryFile ) {
			$testName = substr( basename( $queryFile ), 0, -6 );
			$query = json_decode( file_get_contents( $queryFile ), true );
			$expectedFile = substr( $queryFile, 0, -5 ) . 'expected';
			$tests[$testName] = [ $query, $expectedFile ];
		}

		return $tests;
	}

	/**
	 * @dataProvider searchDataProvider
	 * @param string[] $params query parameters
	 * @param string $expected
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
		$search->setRequest( $this->getMockRequest() );
		$search->setReturnResult( true );
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
		$encodedData = json_encode( $decodedQuery, JSON_PRETTY_PRINT );
		$this->assertFileContains( $expected, $encodedData );
	}

}
