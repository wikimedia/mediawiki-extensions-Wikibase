<?php
/**
 * Created by PhpStorm.
 * User: smalyshev
 * Date: 2/6/17
 * Time: 4:34 PM
 */

namespace Wikibase\Repo\Tests\Api;

use MediaWikiTestCase;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\Api\EntitySearchElastic;

class EntitySearchElasticTest extends MediaWikiTestCase {

	/**
	 * Get a lookup that always returns a pt label and description suffixed by the entity ID
	 *
	 * @return LabelDescriptionLookup
	 */
	private function getMockLabelDescriptionLookup() {
		$mock =
			$this->getMockBuilder( LabelDescriptionLookup::class )
				->disableOriginalConstructor()
				->getMock();
		$mock->expects( $this->any() )
			->method( 'getDescription' )
			->will( $this->returnValue( new Term( 'en', 'DESCRIPTION' ) ) );
		return $mock;
	}

	private function newEntitySearch() {
		$repo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();

		return new EntitySearchElastic( $repo->getLanguageFallbackChainFactory(),
			$repo->getEntityIdParser(), $this->getMockLabelDescriptionLookup(),
			$repo->getContentModelMappings(), $this->getMockRequest() );
	}

	private function getMockRequest() {
		return new \FauxRequest( [ 'cirrusDumpQuery' => 'yes', 'cirrusReturnResult' => 'yes' ] );
	}

	public function searchDataProvider() {
		$tests = [];
		foreach ( glob( __DIR__ . '/../../data/entitySearch/*.query' ) as $queryFile ) {
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
		$this->setMwGlobals( [ 'wgEntitySearchUseCirrus' => true ] );
		$search = $this->newEntitySearch();
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
