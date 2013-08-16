<?php

namespace Wikibase\Test\Api;
use ApiTestCase;

/**
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Adam Shorland
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group GetEntitiesTest
 * @group BreakingTheSlownessBarrier
 *
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 *
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 */
class GetEntitiesTest extends WikibaseApiTestCase {

	private static $hasSetup;
	private static $usedHandles = array( 'Berlin', 'London', 'Oslo' );

	public function setup() {
		parent::setup();

		if( !isset( self::$hasSetup ) ){
			$this->initTestEntities( self::$usedHandles );
		}
		self::$hasSetup = true;
	}

	/**
	 * Test cases are generated using the data provided in the various static arrays below
	 * Adding one extra element to any of the arrays (except format) will generate 4 new tests
	 */
	protected static $goodItems = array(
		//p = params, e = expected
		//handles will automatically be converted to ids
		array( 'p' => array( 'ids' => 'q999999999' ), 'e' => array( 'count' => 1, 'missing' => 1 ) ),
		array( 'p' => array( 'ids' => 'q999999999|q7777777' ), 'e' => array( 'count' => 2, 'missing' => 2 ) ),
		array( 'p' => array( 'sites' => 'enwiki', 'titles' => 'IDoNotExist' ), 'e' => array( 'count' => 1, 'missing' => 1 ) ),
		array( 'p' => array( 'sites' => 'enwiki', 'titles' => 'IDoNotExist|ImNotHere' ), 'e' => array( 'count' => 2, 'missing' => 2 ) ),
		array( 'p' => array( 'handles' => array( 'Berlin' ) ), 'e' => array( 'count' => 1 ) ),
		array( 'p' => array( 'handles' => array( 'London', 'Oslo' ) ), 'e' => array( 'count' => 2 ) ),
		array( 'p' => array( 'handles' => array( 'London', 'London' ) ), 'e' => array( 'count' => 1 ) ),
		array( 'p' => array( 'sites' => 'dewiki', 'titles' => 'Berlin' ), 'e' => array( 'count' => 1 ) ),
		array( 'p' => array( 'sites' => 'dewiki', 'titles' => 'Berlin|London' ), 'e' => array( 'count' => 2 ) ),
		array( 'p' => array( 'sites' => 'dewiki|enwiki', 'titles' => 'Oslo' ), 'e' => array( 'count' => 1 ) ),
		array( 'p' => array( 'sites' => 'dewiki|enwiki', 'titles' => 'Oslo|London' ), 'e' => array( 'count' => 2 ) ),
		array( 'p' => array( 'handles' => array( 'Berlin' ), 'sites' => 'dewiki|enwiki', 'titles' => 'Oslo|London' ), 'e' => array( 'count' => 3 ) ),
		array( 'p' => array( 'sites' => 'dewiki', 'titles' => 'berlin', 'normalize' => '' ), 'e' => array( 'count' => 1, 'normalized' => true ) ),
		array( 'p' => array( 'sites' => 'dewiki', 'titles' => 'Berlin', 'normalize' => '' ), 'e' => array( 'count' => 1, 'normalized' => false ) ),
		array( 'p' => array( 'sites' => 'dewiki', 'titles' => 'hoo', 'normalize' => '' ), 'e' => array( 'count' => 1, 'missing' => 1, 'normalized' => true ) ),
	);
	protected static $goodProps = array( 'info', 'sitelinks', 'aliases', 'labels', 'descriptions', 'claims', 'datatype', 'labels|sitelinks/urls', 'info|aliases|labels|claims' );
	protected static $goodLangs = array( 'de', 'zh', 'it|es|zh|ar', 'de|nn|no|en|en-gb|it|es|zh|ar' );
	protected static $goodSorts = array( array( 'sort' => 'sitelinks', 'dir' => 'descending' ), array( 'sort' => 'sitelinks', 'dir' => 'ascending' ) );
	protected static $goodFormats = array( 'json', 'php', 'wddx', 'xml', 'yaml', 'txt', 'dbg', 'dump' );

	public static function provideData() {
		$testCases = array();

		// Generate test cases based on the static information provided in arrays above
		foreach( self::$goodItems as $itemData ){
			foreach( self::$goodProps  as $propData ){
				foreach( self::$goodLangs as $langData ){
					foreach( self::$goodSorts as $sortData ){
						$testCase['p'] = $itemData['p'];
						$testCase['e'] = $itemData['e'];
						$testCase['p']['props'] = $propData;
						$testCase['p']['languages'] = $langData;
						$testCase['p'] = array_merge( $testCase['p'], $sortData );
						$testCases[] = $testCase;
					}
				}
			}
		}

		// We only want to test each format once so don't include this in the main generation loop
		foreach( self::$goodFormats as $formatData ){
			$testCase = $testCases[0];
			$testCase['p']['format'] = $formatData;
			$testCases[] = $testCase;
		}

		return $testCases;
	}

	/**
	 * This method tests all valid API requests
	 * @dataProvider provideData
	 */
	function testGetEntities( $params, $expected ){
		// -- do the request --------------------------------------------------------
		$ids = array();
		if( array_key_exists( 'handles', $params ) ){
			foreach( $params['handles'] as $handle ){
				//For every id we use we add both the uppercase and lowercase id to the test
				//This then makes sure we only get 1 entity when the only difference between the ids is the case
				$ids[] = strtolower( EntityTestHelper::getId( $handle ) );
				$ids[] = strtoupper( EntityTestHelper::getId( $handle ) );
			}
			$params['ids'] = implode( '|', $ids );
			unset( $params['handles'] );
		}

		$params['action'] = 'wbgetentities';
		$expected = $this->calculateExpectedData( $expected, $params );

		list( $result,, ) = $this->doApiRequest( $params );

		// -- check the result --------------------------------------------------------
		$this->assertArrayHasKey( 'success', $result, "Missing 'success' marker in response." );
		$this->assertArrayHasKey( 'entities', $result, "Missing 'entities' section in response." );
		$this->assertEquals( $expected['count'], count( $result['entities'] ), "Request returned incorrect number of entities" );

		foreach( $result['entities'] as $entityKey => $entity ){
			if( array_key_exists( 'missing', $expected ) && $expected['missing'] > 0 && array_key_exists( 'missing', $entity ) ){
				$this->assertArrayHasKey( 'missing', $entity );
				$this->assertGreaterThanOrEqual( 0, $expected['missing'], 'Got missing entity but not expecting any more' );
				$expected['missing']--;
			} else {
				if( in_array( 'info', $expected['props'] ) ){
					$this->assertPropsInfo( $entity, $expected );
				}
				if( in_array( 'datatype', $expected['props'] ) ){
					$this->assertArrayHasKey( 'type', $entity, 'An entity is missing the type value' );
				}
				if( in_array( 'sitelinks/urls', $expected['props'] ) ){
					$this->assertPropsSitelinksUrls( $entity, $expected );
				}
				if( in_array( 'sitelinks/badges', $expected['props'] ) ){
					$this->assertPropsSitelinksBadges( $entity, $expected );
				}
				if( array_key_exists( 'dir', $expected ) && array_key_exists( 'sitelinks', $entity ) ){
					$this->assertSitelinkSorting( $entity, $expected );
				}
				$this->assertEntityEquals(
					EntityTestHelper::getEntityOutput(
						EntityTestHelper::getHandle( $entity['id'] ),
						$expected['props'],
						$expected['languages']
					),
					$entity
				);
			}
		}

		if( array_key_exists( 'missing', $expected ) ){
			$this->assertEquals( 0, $expected['missing'] );
		}
		if( array_key_exists( 'normalized', $expected ) && $expected['normalized'] === true ){
			$this->assertNormalization( $result, $params );
		} else {
			$this->assertArrayNotHasKey( 'normalized', $result );
		}
	}

	private function calculateExpectedData( $expected, $params ) {
		//expect props
		if( array_key_exists( 'props', $params ) ){
			$expected['props'] = explode( '|', $params['props'] );
		} else {
			$expected['props'] = array( 'info', 'sitelinks', 'aliases', 'labels', 'descriptions', 'claims', 'datatype' );
		}
		//expect languages
		if( array_key_exists( 'languages', $params ) ){
			$expected['languages'] = explode( '|', $params['languages'] );
		} else {
			$expected['languages'] = null;
		}
		//expect order
		if( array_key_exists( 'dir', $params ) ){
			$expected['dir'] = $params['dir'];
		} else {
			$expected['dir'] = 'ascending';
		}
		return $expected;
	}

	private function assertPropsInfo( $entity, $expected ) {
		$this->assertArrayHasKey( 'pageid', $entity, 'An entity is missing the pageid value' );
		$this->assertType( 'integer', $entity['pageid'] );
		$this->assertGreaterThan( 0, $entity['pageid'] );
		$this->assertArrayHasKey( 'ns', $entity, 'An entity is missing the ns value' );
		$this->assertType( 'integer', $entity['ns'] );
		$this->assertGreaterThanOrEqual( 0, $entity['ns'] );
		$this->assertArrayHasKey( 'title', $entity, 'An entity is missing the title value' );
		$this->assertType( 'string', $entity['title'] );
		$this->assertNotEmpty( $entity['title'] );
		$this->assertArrayHasKey( 'lastrevid', $entity, 'An entity is missing the lastrevid value' );
		$this->assertType( 'integer', $entity['lastrevid'] );
		$this->assertGreaterThanOrEqual( 0, $entity['lastrevid'] );
		$this->assertArrayHasKey( 'modified', $entity, 'An entity is missing the modified value' );
		$this->assertRegExp( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z$/', $entity['modified'], "should be in ISO 8601 format" );
		$this->assertArrayHasKey( 'id', $entity, 'An entity is missing the id value' );
		$this->assertArrayHasKey( 'type', $entity, 'An entity is missing the type value' );
	}

	private function assertPropsSitelinksUrls( $entity, $expected ) {
		$expected['props'][] = 'sitelinks';
		foreach( $entity['sitelinks'] as $sitelink ){
			$this->assertArrayHasKey( 'url', $sitelink );
			$this->assertNotEmpty( $sitelink['url'] );
		}
	}

	private function assertPropsSitelinksBadges( $entity, $expected ) {
		$expected['props'][] = 'sitelinks';
		foreach( $entity['sitelinks'] as $sitelink ){
			$this->assertArrayHasKey( 'badge', $sitelink );
			$this->assertNotEmpty( $sitelink['badge'] );
		}
	}

	private function assertSitelinkSorting( $entity, $expected ) {
		$last = '';
		if( $expected['dir'] == 'descending' ){
			$last = 'zzzzzzzz';
		}

		foreach( $entity['sitelinks'] as $link ){
			$site = $link['site'];
			if( $expected['dir'] == 'ascending' ){
				$this->assertTrue( strcmp( $last, $site ) <= 0 , "Failed to assert order of sitelinks, ('{$last}' vs '{$site}') <=0");
			} else {
				$this->assertTrue( strcmp( $last, $site ) >= 0 , "Failed to assert order of sitelinks, ('{$last}' vs '{$site}') >=0");
			}
			$last = $site;
		}
	}

	private function assertNormalization( $result, $params ) {
		$this->assertArrayHasKey( 'normalized', $result );
		$this->assertEquals(
			$params['titles'],
			$result['normalized']['n']['from']
		);
		$this->assertEquals(
		// Normalization in unit tests is actually using Title::getPrefixedText instead of a real API call
			\Title::newFromText( $params['titles'] )->getPrefixedText(),
			$result['normalized']['n']['to']
		);
	}

	public static function provideExceptionData() {
		//todo more exception checks should be added once Bug:53038 is resolved
		return array(
			array( //0 no params
				'p' => array( ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //1 bad id
				'p' => array( 'ids' => 'ABCD' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'no-such-entity' ) ) ),
			array( //2 bad site
				'p' => array( 'sites' => 'qwertyuiop', 'titles' => 'Berlin' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //3 bad and good id
				'p' => array( 'ids' => 'q1|aaaa' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'no-such-entity' ) ) ),
			array( //4 site and no title
				'p' => array( 'sites' => 'enwiki' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //5 title and no site
				'p' => array( 'titles' => 'Berlin' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' ) ) ),
			array( //6 normalization fails with 2 titles
				'p' => array( 'sites' => 'enwiki', 'titles' => 'Foo|Bar' ,'normalize' => '' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'params-illegal' ) ) ),
			array( //7 normalization fails with 2 sites
				'p' => array( 'sites' => 'enwiki|dewiki', 'titles' => 'Boo' ,'normalize' => '' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'params-illegal' ) ) ),
			array( //8 normalization fails with 2 sites and 2 titles
				'p' => array( 'sites' => 'enwiki|dewiki', 'titles' => 'Foo|Bar' ,'normalize' => '' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'params-illegal' ) ) ),
		);
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testGetEntitiesExceptions( $params, $expected ){
		// -- set any defaults ------------------------------------
		$params['action'] = 'wbgetentities';
		if( array_key_exists( 'handles', $params ) ){
			$ids = array();
			foreach( $params['handles'] as $handle ){
				$ids[ $handle ] = EntityTestHelper::getId( $handle );
			}
			$params['ids'] = implode( '|', $ids );
			unset( $params['handles'] );
		}
		$this->doTestQueryExceptions( $params, $expected['exception'] );
	}

}

