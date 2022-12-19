<?php

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;

/**
 * @covers \Wikibase\Repo\Api\GetEntities
 *
 * Test cases are generated using the data provided in the various static arrays below.
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group BreakingTheSlownessBarrier
 * @group Database
 * @group medium
 */
class GetEntitiesTest extends WikibaseApiTestCase {

	private static $hasSetup;
	private static $usedHandles = [ 'StringProp', 'Berlin', 'London', 'Oslo', 'Guangzhou', 'Empty' ];

	protected function setUp(): void {
		parent::setUp();

		if ( !isset( self::$hasSetup ) ) {
			$this->initTestEntities( self::$usedHandles );
		}
		self::$hasSetup = true;
	}

	/**
	 * The key 'p' contains the params, the key 'e' contains things to expect
	 * handles will automatically be converted into the ID pointing to the item
	 */
	protected static $goodItems = [
		[ //1 good id
			'p' => [ 'handles' => [ 'Berlin' ] ],
			'e' => [ 'count' => 1 ] ],
		[ //2 good ids
			'p' => [ 'handles' => [ 'London', 'Oslo' ] ],
			'e' => [ 'count' => 2 ] ],
		[ //1 id requested twice (should only return 1 entity)
			'p' => [ 'handles' => [ 'London', 'London' ] ],
			'e' => [ 'count' => 1 ] ],
		[ //1 good site title combination
			'p' => [ 'sites' => 'dewiki', 'titles' => 'Berlin' ],
			'e' => [ 'count' => 1 ] ],
		[ //2 title, 1 site should return 2 entities
			'p' => [ 'sites' => 'dewiki', 'titles' => 'Berlin|London' ],
			'e' => [ 'count' => 2 ] ],
		[ //2 sites, 1 title should return 1 entity
			'p' => [ 'sites' => 'dewiki|enwiki', 'titles' => 'Oslo' ],
			'e' => [ 'count' => 1 ] ],
		[ //2 sites and 2 titles should return the two entities
			'p' => [ 'sites' => 'dewiki|enwiki', 'titles' => 'Oslo|London' ],
			'e' => [ 'count' => 2 ] ],
		[ //1 id and 2 site title combinations returns 3 entities
			'p' => [ 'handles' => [ 'Berlin' ], 'sites' => 'dewiki|enwiki', 'titles' => 'Oslo|London' ],
			'e' => [ 'count' => 3 ] ],
		[ //1 title with normalization works and gets normalized
			'p' => [ 'sites' => 'dewiki', 'titles' => 'berlin', 'normalize' => '' ],
			'e' => [ 'count' => 1, 'normalized' => true ] ],
		[ //1 title with normalization works and doesn't get normalized if it doesn't need to
			'p' => [ 'sites' => 'dewiki', 'titles' => 'Berlin', 'normalize' => '' ],
			'e' => [ 'count' => 1, 'normalized' => false ] ],
		[ //we still normalise even for non existing pages
			'p' => [ 'sites' => 'dewiki', 'titles' => 'hoo', 'normalize' => '' ],
			'e' => [ 'count' => 1, 'missing' => 1, 'normalized' => true ] ],
		[ //1 non existing item by id
			'p' => [ 'ids' => 'q999999999' ],
			'e' => [ 'count' => 1, 'missing' => 1 ] ],
		[ //2 non existing items by id
			'p' => [ 'ids' => 'q999999999|q7777777' ],
			'e' => [ 'count' => 2, 'missing' => 2 ] ],
		[ //1 non existing item by site and title
			'p' => [ 'sites' => 'enwiki', 'titles' => 'IDoNotExist' ],
			'e' => [ 'count' => 1, 'missing' => 1 ] ],
		[ //2 non existing items by site and title
			'p' => [ 'sites' => 'enwiki', 'titles' => 'IDoNotExist|ImNotHere' ],
			'e' => [ 'count' => 2, 'missing' => 2 ] ],
	];

	/**
	 * goodProps contains many combinations of props that should work when used with the api module
	 * Each property in the array will cause extra assertions when the tests run
	 */
	protected static $goodProps = [
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
	];

	/**
	 * Each language in the array will cause extra assertions when the tests run
	 */
	protected static $goodLangs = [
		//single languages
		'de',
		'zh',
		//multiple languages
		'de|nn|nb|en|en-gb|it|es|zh|ar',
	];

	/**
	 * These are all available formats for the API. we need to make sure they all work
	 * Each format is only tested against the first set of good parameters, from then on json is always used
	 */
	protected static $goodFormats = [
		'json',
		'php',
		'xml',
	];

	/**
	 * This method builds an array of test cases using the data provided in the static arrays above
	 */
	public function provideData() {
		// Test cases for props filter
		foreach ( self::$goodProps  as $propData ) {
			foreach ( self::$goodItems as $testCase ) {
				$testCase['p']['props'] = $propData;
				yield $testCase;
			}
		}

		// Test cases for languages
		foreach ( self::$goodLangs as $langData ) {
			foreach ( self::$goodItems as $testCase ) {
				$testCase['p']['languages'] = $langData;
				yield $testCase;
			}
		}

		// Test cases for different formats (for one item)
		foreach ( self::$goodFormats as $formatData ) {
			$testCase = reset( self::$goodItems );
			$testCase['p']['format'] = $formatData;
			yield $testCase;
		}
	}

	/**
	 * This method tests all valid API requests
	 * @dataProvider provideData
	 */
	public function testGetEntities( array $params, array $expected ) {
		// -- setup any further data -----------------------------------------------
		$params['ids'] = implode( '|', $this->getIdsFromHandlesAndIds( $params ) );
		$params = $this->removeHandles( $params );
		$params['action'] = 'wbgetentities';
		$expected = $this->calculateExpectedData( $expected, $params );

		// -- do the request --------------------------------------------------------
		list( $result,, ) = $this->doApiRequest( $params );

		// -- check the result --------------------------------------------------------
		$this->assertArrayHasKey( 'success', $result, "Missing 'success' marker in response." );
		$this->assertArrayHasKey( 'entities', $result, "Missing 'entities' section in response." );
		$this->assertCount( $expected['count'], $result['entities'],
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

		//Our missing counter should now be at 0, if it is not we have seen too many or not enough missing entities
		if ( array_key_exists( 'missing', $expected ) ) {
			$this->assertSame( 0, $expected['missing'] );
		}

		if ( array_key_exists( 'normalized', $expected ) && $expected['normalized'] === true ) {
			$this->assertNormalization( $result, $params );
		} else {
			$this->assertArrayNotHasKey( 'normalized', $result );
		}
	}

	private function getIdsFromHandlesAndIds( array $params ) {
		if ( array_key_exists( 'ids', $params ) ) {
			$ids = explode( '|', $params['ids'] );
		} else {
			$ids = [];
		}

		if ( array_key_exists( 'handles', $params ) ) {
			foreach ( $params['handles'] as $handle ) {
				//For every id we use we add both the uppercase and lowercase id to the test
				//This then makes sure we only get 1 entity when the only difference between the ids is the case
				$ids[] = strtolower( EntityTestHelper::getId( $handle ) );
				$ids[] = strtoupper( EntityTestHelper::getId( $handle ) );
			}
		}
		return $ids;
	}

	private function removeHandles( array $params ) {
		if ( array_key_exists( 'handles', $params ) ) {
			unset( $params['handles'] );
		}
		return $params;
	}

	private function calculateExpectedData( array $expected, array $params ) {
		//expect the props in params or the default props of the api
		if ( array_key_exists( 'props', $params ) ) {
			$expected['props'] = explode( '|', $params['props'] );
		} else {
			$expected['props'] = [ 'info', 'sitelinks', 'aliases', 'labels', 'descriptions', 'claims', 'datatype' ];
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
		return $expected;
	}

	private function assertEntityResult( array $entity, array $expected ) {
		//Assert individual props of each entity (if we want them, make sure they are there)
		if ( in_array( 'info', $expected['props'] ) ) {
			$this->assertEntityPropsInfo( $entity );
		}
		if ( in_array( 'datatype', $expected['props'] ) ) {
			$this->assertArrayHasKey( 'type', $entity, 'An entity is missing the type value' );
		}
		if ( in_array( 'sitelinks', $expected['props'] ) ) {
			$this->assertEntityPropsSitelinksBadges( $entity );
		}
		if ( in_array( 'sitelinks/urls', $expected['props'] ) ) {
			$this->assertEntityPropsSitelinksUrls( $entity );
		}
		if ( array_key_exists( 'dir', $expected ) && array_key_exists( 'sitelinks', $entity ) ) {
			$this->assertEntitySitelinkSorting( $entity, $expected );
		}

		//Assert the whole entity is as expected (claims, sitelinks, aliases, descriptions, labels)
		$expectedEntityOutput = EntityTestHelper::getEntityOutput(
			EntityTestHelper::getHandle( $entity['id'] ),
			$expected['props'],
			$expected['languages']
		);
		$this->assertEntityEquals(
			$expectedEntityOutput,
			$entity,
			false
		);
	}

	/**
	 * @param array $entity
	 */
	private function assertEntityPropsInfo( array $entity ) {
		$this->assertArrayHasKey( 'pageid', $entity, 'An entity is missing the pageid value' );
		$this->assertIsInt( $entity['pageid'] );
		$this->assertGreaterThan( 0, $entity['pageid'] );

		$this->assertArrayHasKey( 'ns', $entity, 'An entity is missing the ns value' );
		$this->assertIsInt( $entity['ns'] );
		$this->assertGreaterThanOrEqual( 0, $entity['ns'] );

		$this->assertArrayHasKey( 'title', $entity, 'An entity is missing the title value' );
		$this->assertIsString( $entity['title'] );
		$this->assertNotEmpty( $entity['title'] );

		$this->assertArrayHasKey( 'lastrevid', $entity, 'An entity is missing the lastrevid value' );
		$this->assertIsInt( $entity['lastrevid'] );
		$this->assertGreaterThanOrEqual( 0, $entity['lastrevid'] );

		$this->assertArrayHasKey( 'modified', $entity, 'An entity is missing the modified value' );
		$this->assertMatchesRegularExpression(
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
	private function assertEntityPropsSitelinksUrls( array $entity ) {
		foreach ( $entity['sitelinks'] as $siteLink ) {
			$this->assertArrayHasKey( 'url', $siteLink );
			$this->assertNotEmpty( $siteLink['url'] );
		}
	}

	/**
	 * @param array $entity
	 */
	private function assertEntityPropsSitelinksBadges( array $entity ) {
		foreach ( $entity['sitelinks'] as $siteLink ) {
			$this->assertArrayHasKey( 'badges', $siteLink );
			$this->assertIsArray( $siteLink['badges'] );

			foreach ( $siteLink['badges'] as $badge ) {
				$this->assertStringStartsWith( 'Q', $badge );
				$this->assertGreaterThan( 1, strlen( $badge ) );
			}
		}
	}

	private function assertEntitySitelinkSorting( array $entity, array $expected ) {
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

	private function assertNormalization( array $result, array $params ) {
		$this->assertArrayHasKey( 'normalized', $result );
		$this->assertEquals(
			$params['titles'],
			$result['normalized']['n']['from']
		);
		$this->assertEquals(
			// Normalization in unit tests is actually using Title::getPrefixedText instead of a real API call
			\Title::newFromTextThrow( $params['titles'] )->getPrefixedText(),
			$result['normalized']['n']['to']
		);
	}

	public function provideExceptionData() {
		// TODO: More exception checks should be added once bug T55038 is resolved.
		return [
			[ //0 no params
				'p' => [],
				'e' => [ 'exception' => [ 'type' => ApiUsageException::class, 'code' => 'param-missing' ] ] ],
			[ //1 bad id
				'p' => [ 'ids' => 'ABCD' ],
				'e' => [ 'exception' => [ 'type' => ApiUsageException::class, 'code' => 'no-such-entity', 'id' => 'ABCD' ] ] ],
			[ //2 bad site
				'p' => [ 'sites' => 'qwertyuiop', 'titles' => 'Berlin' ],
				'e' => [ 'exception' => [ 'type' => ApiUsageException::class, 'code' => 'param-missing' ] ] ],
			[ //3 bad and good id
				'p' => [ 'ids' => 'q1|aaaa' ],
				'e' => [ 'exception' => [ 'type' => ApiUsageException::class, 'code' => 'no-such-entity', 'id' => 'aaaa' ] ] ],
			[ //4 site and no title
				'p' => [ 'sites' => 'enwiki' ],
				'e' => [ 'exception' => [ 'type' => ApiUsageException::class, 'code' => 'param-missing' ] ] ],
			[ //5 title and no site
				'p' => [ 'titles' => 'Berlin' ],
				'e' => [ 'exception' => [ 'type' => ApiUsageException::class, 'code' => 'param-missing' ] ] ],
			[ //6 normalization fails with 2 titles
				'p' => [ 'sites' => 'enwiki', 'titles' => 'Foo|Bar' ,'normalize' => '' ],
				'e' => [ 'exception' => [ 'type' => ApiUsageException::class, 'code' => 'params-illegal' ] ] ],
			[ //7 normalization fails with 2 sites
				'p' => [ 'sites' => 'enwiki|dewiki', 'titles' => 'Boo' ,'normalize' => '' ],
				'e' => [ 'exception' => [ 'type' => ApiUsageException::class, 'code' => 'params-illegal' ] ] ],
			[ //8 normalization fails with 2 sites and 2 titles
				'p' => [ 'sites' => 'enwiki|dewiki', 'titles' => 'Foo|Bar' ,'normalize' => '' ],
				'e' => [ 'exception' => [ 'type' => ApiUsageException::class, 'code' => 'params-illegal' ] ] ],
			[ //9 must request one site, one title, or an equal number of sites and titles
				'p' => [ 'sites' => 'dewiki|enwiki', 'titles' => 'Oslo|Berlin|London' ],
				'e' => [ 'exception' => [ 'type' => ApiUsageException::class, 'code' => 'params-illegal' ] ] ],
		];
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testGetEntitiesExceptions( array $params, array $expected ) {
		// -- set any defaults ------------------------------------
		$params['action'] = 'wbgetentities';
		if ( array_key_exists( 'handles', $params ) ) {
			$ids = [];
			foreach ( $params['handles'] as $handle ) {
				$ids[ $handle ] = EntityTestHelper::getId( $handle );
			}
			$params['ids'] = implode( '|', $ids );
			unset( $params['handles'] );
		}
		$this->doTestQueryExceptions( $params, $expected['exception'] );
	}

	public function provideLanguageFallback() {
		return [
			'Guangzhou Fallback' => [
				'Guangzhou',
				[ 'de-formal', 'en', 'fr', /* 'yue', */ 'zh-cn', 'zh-hk' ],
				[
					'de-formal' => [
						'language' => 'de',
						'value' => 'Guangzhou',
						'for-language' => 'de-formal',
					],
					/*
					'yue' => [
						'language' => 'yue',
						'value' => '廣州',
					],
					*/
					'zh-cn' => [
						'language' => 'zh-cn',
						'value' => '广州市',
					],
					'zh-hk' => [
						'language' => 'zh-hk',
						'source-language' => 'zh-cn',
						'value' => '廣州市',
					],
				],
				[
					'de-formal' => [
						'language' => 'en',
						'value' => 'Capital of Guangdong.',
						'for-language' => 'de-formal',
					],
					'en' => [
						'language' => 'en',
						'value' => 'Capital of Guangdong.',
					],
					'fr' => [
						'language' => 'en',
						'value' => 'Capital of Guangdong.',
						'for-language' => 'fr',
					],
					/*
					'yue' => [
						'language' => 'zh-hk',
						'value' => '廣東的省會。',
						'for-language' => 'yue',
					],
					*/
					'zh-cn' => [
						'language' => 'zh-cn',
						'source-language' => 'zh-hk',
						'value' => '广东的省会。',
					],
					'zh-hk' => [
						'language' => 'zh-hk',
						'value' => '廣東的省會。',
					],
				],
			],
			'Oslo Fallback' => [
				'Oslo',
				[ 'sli', 'de-formal', 'kn', 'nb' ],
				[
					'sli' => [
						'language' => 'de',
						'value' => 'Oslo',
						'for-language' => 'sli',
					],
					'de-formal' => [
						'language' => 'de',
						'value' => 'Oslo',
						'for-language' => 'de-formal',
					],
					'kn' => [
						'language' => 'en',
						'value' => 'Oslo',
						'for-language' => 'kn',
					],
					'nb' => [
						'language' => 'nb',
						'value' => 'Oslo',
					],
				],
				[
					'sli' => [
						'language' => 'de',
						'value' => 'Hauptstadt der Norwegen.',
						'for-language' => 'sli',
					],
					'de-formal' => [
						'language' => 'de',
						'value' => 'Hauptstadt der Norwegen.',
						'for-language' => 'de-formal',
					],
					'kn' => [
						'language' => 'en',
						'value' => 'Capital city in Norway.',
						'for-language' => 'kn',
					],
					'nb' => [
						'language' => 'nb',
						'value' => 'Hovedsted i Norge.',
					],
				],
			],
			'Oslo Fallback - Labels Only' => [
				'Oslo',
				[ 'sli', 'de-formal', 'kn', 'nb' ],
				[
					'sli' => [
						'language' => 'de',
						'value' => 'Oslo',
						'for-language' => 'sli',
					],
					'de-formal' => [
						'language' => 'de',
						'value' => 'Oslo',
						'for-language' => 'de-formal',
					],
					'kn' => [
						'language' => 'en',
						'value' => 'Oslo',
						'for-language' => 'kn',
					],
					'nb' => [
						'language' => 'nb',
						'value' => 'Oslo',
					],
				],
				null,
				[ 'labels' ],
			],
		];
	}

	/**
	 * @dataProvider provideLanguageFallback
	 */
	public function testLanguageFallback(
		$handle,
		array $languages,
		array $expectedLabels = null,
		array $expectedDescriptions = null,
		array $props = []
	) {
		$id = EntityTestHelper::getId( $handle );

		$params = [
			'action' => 'wbgetentities',
			'languages' => implode( '|', $languages ),
			'languagefallback' => '',
			'ids' => $id,
		];

		if ( !empty( $props ) ) {
			$params['props'] = implode( '|', $props );
		}

		list( $res,, ) = $this->doApiRequest( $params );

		if ( $expectedLabels !== null ) {
			$this->assertEquals( $expectedLabels, $res['entities'][$id]['labels'] );
		}
		if ( $expectedDescriptions !== null ) {
			$this->assertEquals( $expectedDescriptions, $res['entities'][$id]['descriptions'] );
		}
	}

	public function testSiteLinkFilter() {
		$id = EntityTestHelper::getId( 'Oslo' );

		list( $res,, ) = $this->doApiRequest(
			[
				'action' => 'wbgetentities',
				'sitefilter' => 'dewiki|enwiki',
				'format' => 'json', // make sure IDs are used as keys
				'ids' => $id,
			]
		);

		$expectedSiteLinks = [
			'dewiki' => [
				'site' => 'dewiki',
				'title' => 'Oslo',
				'badges' => [],
			],
			'enwiki' => [
				'site' => 'enwiki',
				'title' => 'Oslo',
				'badges' => [],
			],
		];
		$this->assertEquals( $expectedSiteLinks, $res['entities'][$id]['sitelinks'] );
	}

	public function testGivenEntityIdFromUnknownRepository_getEntitiesMarksEntityAsMissing() {
		// Use an entity id that exists locally, but pretend it's on another repo.
		$id = 'FOOOOO:' . EntityTestHelper::getId( 'Oslo' );
		$result = $this->doApiRequest( [ 'action' => 'wbgetentities', 'ids' => $id ] );

		$this->assertEquals(
			[
				'id' => $id,
				'missing' => '',
			],
			$result[0]['entities'][$id]
		);
	}

}
