<?php

namespace Wikibase\Test\Api;

use OutOfBoundsException;
use Title;
use Wikibase\Api\GetEntities;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Item;

/**
 * @covers Wikibase\Api\GetEntities
 *
 * Test cases are generated using the data provided in the various static arrays below
 * Adding one extra element to any of the arrays (except format) will generate 4 new tests
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group GetEntitiesTest
 * @group BreakingTheSlownessBarrier
 * @group Database
 * @group medium
 */
class GetEntitiesTest extends IndependentWikibaseApiTestCase  {

	/**
	 * @see NewWikibaseApiTestCase::getModuleClass
	 */
	protected function getModuleClass() {
		return '\Wikibase\Api\GetEntities';
	}

	/**
	 * @see NewWikibaseApiTestCase::getModule
	 */
	protected function getModule( $params ) {
		/** @var GetEntities $module */
		$module = parent::getModule( $params );
		$module->overrideServices(
			$this->getMockEntityTitleLookup(),
			$this->getMockEntityRevisionLookup(),
			$this->getMockEntityStore(),
			$this->getMockSiteLinkCache(),
			$this->getMockSiteLinkTargetProvider()
		);
		return $module;
	}

	/**
	 * The key 'p' contains the params, the key 'e' contains things to expect
	 */
	protected static $goodItems = array(
		array( //1 good id
			'p' => array( 'ids' => 'Q3' ),
			'e' => array( 'count' => 1 ) ),
		array( //2 good ids
			'p' => array( 'ids' => 'Q4|Q5' ),
			'e' => array( 'count' => 2 ) ),
		array( //1 id requested twice (should only return 1 entity)
			'p' => array( 'ids' => 'Q4|Q4' ),
			'e' => array( 'count' => 1 ) ),
		array( //1 good site title combination
			'p' => array( 'sites' => 'dewiki', 'titles' => 'Berlin' ),
			'e' => array( 'count' => 1 ) ),
		array( //2 title, 1 site should return 2 entities
			'p' => array( 'sites' => 'dewiki', 'titles' => 'Berlin|London' ),
			'e' => array( 'count' => 2 ) ),
		array( //2 sites, 1 title should return 1 entity
			'p' => array( 'sites' => 'dewiki|enwiki', 'titles' => 'Oslo' ),
			'e' => array( 'count' => 1 ) ),
		array( //2 sites and 2 titles should return the two entities
			'p' => array( 'sites' => 'dewiki|enwiki', 'titles' => 'Oslo|London' ),
			'e' => array( 'count' => 2 ) ),
		array( //1 id and 2 site title combinations returns 3 entities
			'p' => array( 'ids' => 'Q3', 'sites' => 'dewiki|enwiki', 'titles' => 'Oslo|London' ),
			'e' => array( 'count' => 3 ) ),
		array( //1 title with normalization works and gets normalized
			'p' => array( 'sites' => 'dewiki', 'titles' => 'berlin', 'normalize' => '' ),
			'e' => array( 'count' => 1, 'normalized' => true ) ),
		array( //1 title with normalization works and doesn't get normalized if it doesn't need to
			'p' => array( 'sites' => 'dewiki', 'titles' => 'Berlin', 'normalize' => '' ),
			'e' => array( 'count' => 1, 'normalized' => false ) ),
		array( //we still normalise even for non existing pages
			'p' => array( 'sites' => 'dewiki', 'titles' => 'hoo', 'normalize' => '' ),
			'e' => array( 'count' => 1, 'missing' => 1, 'normalized' => true ) ),
		array( //1 non existing item by id
			'p' => array( 'ids' => 'q999999999' ),
			'e' => array( 'count' => 1, 'missing' => 1 ) ),
		array( //2 non existing items by id
			'p' => array( 'ids' => 'q999999999|q7777777' ),
			'e' => array( 'count' => 2, 'missing' => 2 ) ),
		array( //1 non existing item by site and title
			'p' => array( 'sites' => 'enwiki', 'titles' => 'IDoNotExist' ),
			'e' => array( 'count' => 1, 'missing' => 1 ) ),
		array( //2 non existing items by site and title
			'p' => array( 'sites' => 'enwiki', 'titles' => 'IDoNotExist|ImNotHere' ),
			'e' => array( 'count' => 2, 'missing' => 2 ) ),
	);

	/**
	 * goodProps contains many combinations of props that should work when used with the api module
	 * Each property in the array will cause extra assertions when the tests run
	 */
	protected static $goodProps = array(
		//individual props
		'info',
		'sitelinks',
		'aliases',
		'labels',
		'descriptions',
		'claims',
		'datatype',
		//multiple props
		'labels|sitelinks/urls|info|claims',
	);

	/**
	 * Each language in the array will cause extra assertions when the tests run
	 */
	protected static $goodLangs = array(
		//single languages
		'de',
		'zh',
		//multiple languages
		'de|nn|nb|en|en-gb|it|es|zh|ar'
	);

	/**
	 * Possible sorts, will cause extra assertions
	 */
	protected static $goodSorts = array(
		array( 'sort' => 'sitelinks', 'dir' => 'descending' ),
		array( 'sort' => 'sitelinks', 'dir' => 'ascending' )
	);

	/**
	 * These are all available formats for the API. we need to make sure they all work.
	 * Each format is only tested against the first set of good parameters.
	 * Json is used for all other requests.
	 */
	protected static $goodFormats = array(
		'json',
		'php',
		'wddx',
		'xml',
		'yaml',
		'txt',
		'dbg',
		'dump'
	);

	/**
	 * This method builds an array of test cases using the data provided in the static arrays above
	 * @return array
	 */
	public static function provideData() {
		$testCases = array();

		// Generate test cases based on the static information provided in arrays above
		foreach ( self::$goodItems as $itemData ) {
			foreach ( self::$goodProps  as $propData ) {
				foreach ( self::$goodLangs as $langData ) {
					foreach ( self::$goodSorts as $sortData ) {
						$testCase['p'] = $itemData['p'];
						$testCase['e'] = $itemData['e'];
						$testCase['p']['props'] = $propData;
						$testCase['p']['languages'] = $langData;
						$testCase['p'] = array_merge( $testCase['p'], $sortData );
						$testCases[] = $testCase;
						if( in_array( 'claims', explode( '|', $propData ) ) ){
							$testCase['p']['ungroupedlist'] = true;
							$testCases[] = $testCase;
						}
					}
				}
			}
		}

		// We only want to test each format once so don't include this in the main generation loop
		foreach ( self::$goodFormats as $formatData ) {
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
	public function testGetEntities( $params, $expected ) {
		// -- setup any further data -----------------------------------------------
		$params['action'] = 'wbgetentities';
		$expected = $this->calculateExpectedData( $expected, $params );

		// -- do the request --------------------------------------------------------
		$result = $this->doApiRequest( $params );

		// -- check the result --------------------------------------------------------
		$this->assertArrayHasKey( 'success', $result, "Missing 'success' marker in response." );
		$this->assertArrayHasKey( 'entities', $result, "Missing 'entities' section in response." );
		$this->assertEquals( $expected['count'], count( $result['entities'] ),
			"Request returned incorrect number of entities" );

		foreach ( $result['entities'] as $entity ) {
			if ( array_key_exists( 'missing', $expected ) && array_key_exists( 'missing', $entity ) ) {
				$this->assertArrayHasKey( 'missing', $entity );
				$this->assertGreaterThanOrEqual( 0, $expected['missing'],
					'Got missing entity but not expecting any more' );
				$expected['missing']--;

			} else {
				$this->assertEntityResult( $entity, $expected );
			}
		}

		// Missing counter should be at 0, if not we have seen the wrong number of missing entities
		if ( array_key_exists( 'missing', $expected ) ) {
			$this->assertEquals( 0, $expected['missing'] );
		}

		if ( array_key_exists( 'normalized', $expected ) && $expected['normalized'] === true ) {
			$this->assertNormalization( $result, $params );
		} else {
			$this->assertArrayNotHasKey( 'normalized', $result );
		}
	}

	private function calculateExpectedData( $expected, $params ) {
		//expect the props in params or the default props of the api
		if ( array_key_exists( 'props', $params ) ) {
			$expected['props'] = explode( '|', $params['props'] );
		} else {
			$expected['props'] = array(
				'info',
				'sitelinks',
				'aliases',
				'labels',
				'descriptions',
				'claims',
				'datatype'
			);
		}

		//implied props
		if ( in_array( 'sitelinks/urls', $expected['props'] ) ) {
			$expected['props'][] = 'sitelinks';
		}

		//expect the languages in params or just all languages
		if ( array_key_exists( 'languages', $params ) ) {
			$expected['languages'] = explode( '|', $params['languages'] );
		} else {
			$expected['languages'] = null;
		}
		//expect order in params or expect default
		if ( array_key_exists( 'dir', $params ) ) {
			$expected['dir'] = $params['dir'];
		} else {
			$expected['dir'] = 'ascending';
		}

		//expect snaks to be grouped by property or not
		if( !isset( $params['ungroupedlist'] ) || !$params['ungroupedlist'] ) {
			$expected['groupedbyproperty'] = true;
		} else {
			$expected['groupedbyproperty'] = false;
		}
		return $expected;
	}

	/**
	 * @param array $entity
	 * @param array $expected
	 */
	private function assertEntityResult( $entity, $expected ) {
		//Assert individual props of each entity (if we want them, make sure they are there)
		if ( in_array( 'info', $expected['props'] ) ) {
			$this->assertEntityPropsInfo( $entity );
		}
		if ( in_array( 'datatype', $expected['props'] ) ) {
			$this->assertArrayHasKey( 'type', $entity, 'An entity is missing the type value' );
		}
		if ( array_key_exists( 'dir', $expected ) && array_key_exists( 'sitelinks', $entity ) ) {
			$this->assertEntitySitelinkSorting( $entity, $expected );
		}
		if( in_array( 'labels', $expected['props'] ) ) {
			$this->assertEntityLabels( $entity, $expected['languages'] );
		}
		if( in_array( 'descriptions', $expected['props'] ) ) {
			$this->assertEntityDescriptions( $entity, $expected['languages'] );
		}
		if( in_array( 'aliases', $expected['props'] ) ) {
			$this->assertEntityAliases( $entity, $expected['languages'] );
		}
		if ( in_array( 'sitelinks', $expected['props'] ) ) {
			$this->assertEntitySitelinks( $entity );
			$this->assertEntityPropsSitelinksBadges( $entity );
		}
		if ( in_array( 'sitelinks/urls', $expected['props'] ) ) {
			$this->assertEntityPropsSitelinksUrls( $entity );
		}
		if( in_array( 'claims', $expected['props'] ) ) {
			$this->assertEntityClaims( $entity, $expected['groupedbyproperty'] );
		}
	}

	/**
	 * @param array $apiEntity
	 * @param array $requestedLangs
	 */
	private function assertEntityLabels( $apiEntity, $requestedLangs ) {
		$expected = EntityTestHelper::getTestEntity( $apiEntity['id'] );
		$expectedLabels = $expected->getFingerprint()->getLabels()->toTextArray();
		foreach( $requestedLangs as $langCode ) {
			if( array_key_exists( $langCode, $expectedLabels ) ) {
				$this->assertArrayHasKey( $langCode, $apiEntity['labels'] );
				$this->assertEquals( $langCode, $apiEntity['labels'][$langCode]['language'] );
				$this->assertEquals( $expectedLabels[$langCode], $apiEntity['labels'][$langCode]['value'] );
			}
		}
	}

	/**
	 * @param array $apiEntity
	 * @param array $requestedLangs
	 */
	private function assertEntityDescriptions( $apiEntity, $requestedLangs ) {
		$expected = EntityTestHelper::getTestEntity( $apiEntity['id'] );
		$expectedDescriptions = $expected->getFingerprint()->getDescriptions()->toTextArray();
		foreach( $requestedLangs as $langCode ) {
			if( array_key_exists( $langCode, $expectedDescriptions ) ) {
				$this->assertArrayHasKey( $langCode, $apiEntity['descriptions'] );
				$this->assertEquals( $langCode, $apiEntity['descriptions'][$langCode]['language'] );
				$this->assertEquals(
					$expectedDescriptions[$langCode],
					$apiEntity['descriptions'][$langCode]['value']
				);
			}
		}
	}

	/**
	 * @param array $apiEntity
	 * @param array $requestedLangs
	 */
	private function assertEntityAliases( $apiEntity, $requestedLangs ) {
		$expected = EntityTestHelper::getTestEntity( $apiEntity['id'] );
		$expectedAliases = $expected->getFingerprint()->getAliases();
		foreach( $requestedLangs as $langCode ) {

			// TODO FIXME the below can probably be done in a nicer way once
			//  extra methods are added to the DataModel
			try{
				$expectedLangAlias = $expectedAliases->getByLanguage( $langCode )->getAliases();
				$this->assertArrayHasKey( $langCode, $apiEntity['aliases'] );
				$apiAliasGroup = $apiEntity['aliases'][$langCode];
				$this->assertEquals( count( $expectedLangAlias ), count( $apiAliasGroup ) );
				foreach( $apiAliasGroup as $apiAlias ) {
					$this->assertArrayHasKey( 'value', $apiAlias );
					$this->assertArrayHasKey( 'language', $apiAlias );
					$this->assertEquals( $langCode, $apiAlias['language'] );
					$this->assertTrue( in_array( $apiAlias['value'], $expectedLangAlias ) );
				}
			}
			catch( OutOfBoundsException $e ){
				// No expected aliases for this language so no need to check
			}
		}
	}

	/**
	 * @param array $apiEntity
	 */
	private function assertEntitySitelinks( $apiEntity ) {
		/** @var Item $expected */
		$expected = EntityTestHelper::getTestEntity( $apiEntity['id'] );
		$expectedSitelinks = $expected->getSiteLinks();
		$apiSitelinks = $apiEntity['sitelinks'];
		$this->assertEquals( count( $apiSitelinks ), count( $expectedSitelinks ) );
		foreach( $expectedSitelinks as $expectedSiteLink ) {
			$siteId = $expectedSiteLink->getSiteId();
			$this->assertArrayHasKey( $siteId, $apiSitelinks );
			$this->assertEquals( $siteId, $apiSitelinks[$siteId]['site'] );
			$this->assertEquals( $expectedSiteLink->getPageName(), $apiSitelinks[$siteId]['title'] );
			$this->assertEquals( $expectedSiteLink->getBadges(), $apiSitelinks[$siteId]['badges'] );
		}
	}

	/**
	 * @param array $apiEntity
	 * @param bool $groupedByProperty
	 */
	private function assertEntityClaims( $apiEntity, $groupedByProperty ) {
		$expected = EntityTestHelper::getTestEntity( $apiEntity['id'] );
		$expectedClaims = new Claims( $expected->getClaims() );

		if( !array_key_exists( 'claims', $apiEntity ) ) {
			$apiClaims = array();
		} elseif( $groupedByProperty ) {
			$apiClaims = array();
			foreach( $apiEntity['claims'] as $propertyGroup ) {
				foreach( $propertyGroup as $claim ) {
					$apiClaims[] = $claim;
				}
			}
		} else {
			$apiClaims = $apiEntity['claims'];
		}

		$this->assertEquals( $expectedClaims->count(), count( $apiClaims ) );
		foreach( $apiClaims as $apiClaim ) {
			$this->assertTrue( $expectedClaims->hasClaimWithGuid( $apiClaim['id'] ) );
		}
	}

	/**
	 * @param array $entity
	 */
	private function assertEntityPropsInfo( $entity ) {
		$this->assertArrayHasKey( 'pageid', $entity, 'An entity is missing the pageid value' );
		$this->assertInternalType( 'integer', $entity['pageid'] );
		$this->assertGreaterThan( 0, $entity['pageid'] );

		$this->assertArrayHasKey( 'ns', $entity, 'An entity is missing the ns value' );
		$this->assertInternalType( 'integer', $entity['ns'] );
		$this->assertGreaterThanOrEqual( 0, $entity['ns'] );

		$this->assertArrayHasKey( 'title', $entity, 'An entity is missing the title value' );
		$this->assertInternalType( 'string', $entity['title'] );
		$this->assertNotEmpty( $entity['title'] );

		$this->assertArrayHasKey( 'lastrevid', $entity, 'An entity is missing the lastrevid value' );
		$this->assertInternalType( 'integer', $entity['lastrevid'] );
		$this->assertGreaterThanOrEqual( 0, $entity['lastrevid'] );

		$this->assertArrayHasKey( 'modified', $entity, 'An entity is missing the modified value' );
		$this->assertRegExp(
			'/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z$/',
			$entity['modified'],
			"should be in ISO 8601 format"
		);

		$this->assertArrayHasKey( 'id', $entity, 'An entity is missing the id value' );
		$this->assertArrayHasKey( 'type', $entity, 'An entity is missing the type value' );
	}

	/**
	 * @param array $entity
	 */
	private function assertEntityPropsSitelinksUrls( $entity ) {
		foreach ( $entity['sitelinks'] as $sitelink ) {
			$this->assertArrayHasKey( 'url', $sitelink );
			$this->assertNotEmpty( $sitelink['url'] );
		}
	}

	/**
	 * @param array $entity
	 */
	private function assertEntityPropsSitelinksBadges( $entity ) {
		foreach ( $entity['sitelinks'] as $sitelink ) {
			$this->assertArrayHasKey( 'badges', $sitelink );
			$this->assertInternalType( 'array', $sitelink['badges'] );

			foreach ( $sitelink['badges'] as $badge ) {
				$this->assertStringStartsWith( 'Q', $badge );
				$this->assertGreaterThan( 1, strlen( $badge ) );
			}
		}
	}

	private function assertEntitySitelinkSorting( $entity, $expected ) {
		$last = '';
		if ( $expected['dir'] == 'descending' ) {
			$last = 'zzzzzzzz';
		}

		foreach ( $entity['sitelinks'] as $link ) {
			$site = $link['site'];

			if ( $expected['dir'] == 'ascending' ) {
				$this->assertTrue(
					strcmp( $last, $site ) <= 0,
					"Failed to assert order of sitelinks, ('{$last}' vs '{$site}') <=0"
				);
			} else {
				$this->assertTrue(
					strcmp( $last, $site ) >= 0,
					"Failed to assert order of sitelinks, ('{$last}' vs '{$site}') >=0"
				);
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
			// Normalization in unit tests is mocked
			Title::newFromText( $params['titles'] )->getPrefixedText(),
			$result['normalized']['n']['to']
		);
	}

	public static function provideExceptionData() {
		//TODO more exception checks should be added once Bug:53038 is resolved
		return array(
			array( //0 no params
				'p' => array( ),
				'e' => array(
					'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' )
				) ),
			array( //1 bad id
				'p' => array( 'ids' => 'ABCD' ),
				'e' => array(
					'exception' => array( 'type' => 'UsageException', 'code' => 'no-such-entity' )
				) ),
			array( //2 bad site
				'p' => array( 'sites' => 'qwertyuiop', 'titles' => 'Berlin' ),
				'e' => array(
					'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' )
				) ),
			array( //3 bad and good id
				'p' => array( 'ids' => 'q1|aaaa' ),
				'e' => array(
					'exception' => array( 'type' => 'UsageException', 'code' => 'no-such-entity' )
				) ),
			array( //4 site and no title
				'p' => array( 'sites' => 'enwiki' ),
				'e' => array(
					'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' )
				) ),
			array( //5 title and no site
				'p' => array( 'titles' => 'Berlin' ),
				'e' => array(
					'exception' => array( 'type' => 'UsageException', 'code' => 'param-missing' )
				) ),
			array( //6 normalization fails with 2 titles
				'p' => array( 'sites' => 'enwiki', 'titles' => 'Foo|Bar' ,'normalize' => '' ),
				'e' => array(
					'exception' => array( 'type' => 'UsageException', 'code' => 'params-illegal' )
				) ),
			array( //7 normalization fails with 2 sites
				'p' => array( 'sites' => 'enwiki|dewiki', 'titles' => 'Boo' ,'normalize' => '' ),
				'e' => array(
					'exception' => array( 'type' => 'UsageException', 'code' => 'params-illegal' )
				) ),
			array( //8 normalization fails with 2 sites and 2 titles
				'p' => array( 'sites' => 'enwiki|dewiki', 'titles' => 'Foo|Bar' ,'normalize' => '' ),
				'e' => array(
					'exception' => array( 'type' => 'UsageException', 'code' => 'params-illegal' )
				) ),
			array( //9 must request one site, one title, or an equal number of sites and titles
				'p' => array( 'sites' => 'dewiki|enwiki', 'titles' => 'Oslo|Berlin|London' ),
				'e' => array(
					'exception' => array( 'type' => 'UsageException', 'code' => 'params-illegal' )
				) ),
		);
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testGetEntitiesExceptions( $params, $expected ) {
		// -- set any defaults ------------------------------------
		$params['action'] = 'wbgetentities';
		if ( array_key_exists( 'handles', $params ) ) {
			$ids = array();
			foreach ( $params['handles'] as $handle ) {
				$ids[ $handle ] = EntityTestHelper::getId( $handle );
			}
			$params['ids'] = implode( '|', $ids );
			unset( $params['handles'] );
		}
		$this->doTestQueryExceptions( $params, $expected['exception'] );
	}

		public function provideLanguageFallback() {
		return array(
			array(
				'Q8',
				array( 'de-formal', 'en', 'fr', 'yue', 'zh-cn', 'zh-hk' ),
				array(
					'de-formal' => array(
						'language' => 'de',
						'value' => 'Guangzhou',
					),
					'yue' => array(
						'language' => 'yue',
						'value' => '廣州',
					),
					'zh-cn' => array(
						'language' => 'zh-cn',
						'value' => '广州市',
					),
					'zh-hk' => array(
						'language' => 'zh-hk',
						'source-language' => 'zh-cn',
						'value' => '廣州市',
					),
				),
				array(
					'de-formal' => array(
						'language' => 'en',
						'value' => 'Capital of Guangdong.',
					),
					'en' => array(
						'language' => 'en',
						'value' => 'Capital of Guangdong.',
					),
					'fr' => array(
						'language' => 'en',
						'value' => 'Capital of Guangdong.',
					),
					'yue' => array(
						'language' => 'en',
						'value' => 'Capital of Guangdong.',
					),
					'zh-cn' => array(
						'language' => 'zh-cn',
						'source-language' => 'zh-hk',
						'value' => '广东的省会。',
					),
					'zh-hk' => array(
						'language' => 'zh-hk',
						'value' => '廣東的省會。',
					),

				),
			),
		);
	}

	/**
	 * @dataProvider provideLanguageFallback
	 * @todo factor this into the main test method
	 */
	public function testLanguageFallback( $id, $languages, $expectedLabels, $expectedDescriptions ) {
		$res = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'languages' => join( '|', $languages ),
				'languagefallback' => '',
				'format' => 'json', // make sure IDs are used as keys
				'ids' => $id,
			)
		);

		$this->assertEquals( $expectedLabels, $res['entities'][$id]['labels'] );
		$this->assertEquals( $expectedDescriptions, $res['entities'][$id]['descriptions'] );
	}

	public function testSitelinkFilter () {
		$id = 'Q5';

		$res = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'sitefilter' => 'dewiki|enwiki',
				'format' => 'json', // make sure IDs are used as keys
				'ids' => $id,
			)
		);

		$expectedSitelinks = array(
			'dewiki' => array(
				'site' => 'dewiki',
				'title' => 'Oslo',
				'badges' => array(),
			),
			'enwiki' => array(
				'site' => 'enwiki',
				'title' => 'Oslo',
				'badges' => array(),
			),
		);
		$this->assertEquals( $expectedSitelinks, $res['entities'][$id]['sitelinks'] );
	}

}
