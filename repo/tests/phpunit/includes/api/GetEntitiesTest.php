<?php

namespace Wikibase\Test\Api;
use ApiTestCase;

/**
 * Tests for the ApiWikibase class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 * @author Marius Hoch < hoo@online.de >
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
	private static $usedHandles = array( 'Berlin', 'London', 'Oslo', 'Leipzig', 'Guangzhou', 'Empty' );

	public function setup() {
		parent::setup();

		if( !isset( self::$hasSetup ) ){
			$this->initTestEntities( self::$usedHandles );
		}
		self::$hasSetup = true;
	}

	/**
	 * @dataProvider provideEntityHandles
	 */
	function testGetItemById( $handle ) {
		$id = EntityTestHelper::getId( $handle );
		$item = EntityTestHelper::getEntityOutput( $handle );

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'format' => 'json', // make sure IDs are used as keys
				'ids' => $id )
		);

		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities', $id );
		$this->assertEntityEquals( $item,  $res['entities'][$id] );
		$this->assertEquals( 1, count( $res['entities'] ), "requesting a single item should return exactly one item entry" );
		// This should be correct for all items we are testing
		$this->assertEquals( \Wikibase\Item::ENTITY_TYPE,  $res['entities'][$id]['type'] );
		// The following comes from the props=info which is included by default
		// Only check if they are there and seems valid, can't do much more for the moment (or could for title but then we are testing assumptions)
		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities', $id, 'pageid' );
		$this->assertTrue( is_integer( $res['entities'][$id]['pageid'] ) );
		$this->assertTrue( 0 < $res['entities'][$id]['pageid'] );
		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities', $id, 'ns' );
		$this->assertTrue( is_integer( $res['entities'][$id]['ns'] ) );
		$this->assertTrue( 0 <= $res['entities'][$id]['ns'] );
		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities', $id, 'title' );
		$this->assertTrue( is_string( $res['entities'][$id]['title'] ) );
		$this->assertTrue( 0 < strlen( $res['entities'][$id]['title'] ) );
		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities', $id, 'lastrevid' );
		$this->assertTrue( is_integer( $res['entities'][$id]['lastrevid'] ) );
		$this->assertTrue( 0 < $res['entities'][$id]['lastrevid'] );
		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities', $id, 'modified' );
		$this->assertTrue( is_string( $res['entities'][$id]['modified'] ) );
		$this->assertTrue( 0 < strlen( $res['entities'][$id]['modified'] ) );
		$this->assertRegExp( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z$/',
			$res['entities'][$id]['modified'], "should be in ISO 8601 format" );

		//@todo: check non-item
	}

	/**
	 * Verify that we only get one item if we ask for the same item several
	 * times in different forms.
	 *
	 * @dataProvider provideEntityHandles
	 */
	function testGetItemByIdUnique( $handle ) {
		$id = EntityTestHelper::getId( $handle );
		$item = EntityTestHelper::getEntityOutput( $handle );

		$ids = array(
			strtoupper( $id ),
			strtolower( $id ),
			$id, $id
		);
		$ids = join( '|', $ids );

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'format' => 'json', // make sure IDs are used as keys
				'ids' => $ids )
		);

		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities', $id );
		$this->assertEntityEquals( $item,  $res['entities'][$id] );
		$this->assertEquals( 1, count( $res['entities'] ) );
	}

	/**
	 * data provider for passing each entity handle to the test function.
	 */
	public static function provideEntityHandles() {
		$handles = array();
		foreach( self::$usedHandles as $handle ){
			$handles[] = array( $handle );
		}
		return $handles;
	}

	public static function provideGetItemByTitle() {
		$calls = array();

		foreach ( self::$usedHandles as $handle ) {
			$item = EntityTestHelper::getEntityData( $handle );

			if ( !array_key_exists( 'sitelinks', $item ) ) {
				continue;
			}

			foreach ( $item['sitelinks'] as $sitelink ) {
				$calls[] = array( $handle, $sitelink['site'], $sitelink['title'] );
			}
		}

		return $calls;
	}

	/**
	 * Test basic lookup of items to get the id.
	 * This is really a fast lookup without reparsing the stringified item.
	 *
	 * @dataProvider provideGetItemByTitle
	 */
	public function testGetItemByTitle( $handle, $site, $title ) {
		list($res,,) = $this->doApiRequest( array(
			'action' => 'wbgetentities',
			'sites' => $site,
			'titles' => $title,
			'format' => 'json', // make sure IDs are used as keys
		) );

		$id = EntityTestHelper::getId( $handle );
		$item = EntityTestHelper::getEntityOutput( $handle );

		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities', $id );
		$this->assertEntityEquals( $item,  $res['entities'][$id] );
		$this->assertEquals( 1, count( $res['entities'] ), "requesting a single item should return exactly one item entry" );
	}

	/**
	 * Test basic lookup of items to get the id.
	 * This is really a fast lookup without reparsing the stringified item.
	 *
	 * @dataProvider provideGetItemByTitle
	 */
	public function testGetItemByTitleNormalized( $handle, $site, $title ) {
		$title = lcfirst( $title );
		list($res,,) = $this->doApiRequest( array(
			'action' => 'wbgetentities',
			'sites' => $site,
			'titles' => $title,
			'normalize' => true,
			'format' => 'json', // make sure IDs are used as keys
		) );

		$id = EntityTestHelper::getId( $handle );
		$item = EntityTestHelper::getEntityOutput( $handle );

		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities', $id );
		$this->assertEntityEquals( $item,  $res['entities'][$id] );
		$this->assertEquals( 1, count( $res['entities'] ), "requesting a single item should return exactly one item entry" );

		// The normalization that has been applied should be noted
		$this->assertEquals(
			$title,
			$res['normalized']['n']['from']
		);

		$this->assertEquals(
			// Normalization in unit tests is actually using Title::getPrefixedText instead of a real API call
			\Title::newFromText( $title )->getPrefixedText(),
			$res['normalized']['n']['to']
		);
	}

	/**
	 * @return array
	 */
	public static function provideGetEntitiesNormalizationNotAllowed() {
		return array(
			array(
				// Two sites one page
				array( 'enwiki', 'dewiki' ),
				array( 'Foo' )
			),
			array(
				// Two pages one site
				array( 'enwiki' ),
				array( 'Foo', 'Bar' )
			),
			array(
				// Two sites two pages
				array( 'enwiki', 'dewiki' ),
				array( 'Foo', 'Bar' )
			)
		);
	}

	/**
	 * Test that the API is throwing an error if the users wants to
	 * normalize with multiple sites/ pages.
	 *
	 * @group API
	 * @dataProvider provideGetEntitiesNormalizationNotAllowed
	 *
	 * @param array $sites
	 * @param array $titles
	 */
	public function testGetEntitiesNormalizationNotAllowed( $sites, $titles ) {
		$this->setExpectedException( 'UsageException' );

		list($res,,) = $this->doApiRequest( array(
			'action' => 'wbgetentities',
			'sites' => join( '|', $sites ),
			'titles' => join( '|', $titles ),
			'normalize' => true
		) );
	}

	/**
	 * Test that the API isn't showing the normalization note in case nothing changed.
	 *
	 * @group API
	 */
	public function testGetEntitiesNoNormalizationApplied( ) {
		list($res,,) = $this->doApiRequest( array(
			'action' => 'wbgetentities',
			'sites' => 'enwiki',
			'titles' => 'HasNoItemAndIsNormalized',
			'normalize' => true
		) );

		$this->assertFalse( isset( $res['normalized'] ) );
	}

	/**
	 * Testing if we can get missing items if we do lookup with single fake ids.
	 * Note that this makes assumptions about which ids have been assigned.
	 *
	 * @group API
	 */
	public function testGetEntitiesByBadId( ) {
		$badid = 'q123456789';
		list( $res,, ) = $this->doApiRequest( array(
			'action' => 'wbgetentities',
			'ids' => $badid,
		) );

		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities', $badid, 'missing' );
		$this->assertEquals( 1, count( $res['entities'] ), "requesting a single item should return exactly one item entry" );
	}

	/**
	 * Testing behavior for malformed entity ids.
	 *
	 * @group API
	 */
	public function testGetEntitiesByMalformedId( ) {
		$this->setExpectedException( 'UsageException' );
		$badid = 'xyz123+++';
		$this->doApiRequest( array(
			'action' => 'wbgetentities',
			'ids' => $badid,
		) );
	}

	/**
	 * Testing if we can get an item using a site that is not part of the group supported for sitelinks.
	 * Note that this makes assumptions about which sitelinks exist in the test items.
	 *
	 * @group API
	 */
	public function testGetEntitiesByBadSite( ) {
		$this->setExpectedException( 'UsageException' );
		$this->doApiRequest( array(
			'action' => 'wbgetentities',
			'sites' => 'enwiktionary',
			'titles' => 'Berlin',
		) );
	}

	/**
	 * Testing if we can get missing items if we do lookup with failing titles.
	 * Note that this makes assumptions about which sitelinks they have been assigned.
	 *
	 * @group API
	 */
	public function testGetEntitiesByBadTitle( ) {
		list( $res,, ) = $this->doApiRequest( array(
			'action' => 'wbgetentities',
			'sites' => 'enwiki',
			'titles' => 'klaijehrnqowienxcqopweiu',
		) );

		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities' );

		$keys = array_keys( $res['entities'] );
		$this->assertEquals( 1, count( $keys ), "requesting a single item should return exactly one item entry" );

		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities', $keys[0], 'missing' );
	}

	/**
	 * Testing if we can get missing items if we do lookup with failing titles and
	 * that the normalization that has been applied is being noted correctly.
	 *
	 * Note that this makes assumptions about which sitelinks they have been assigned.
	 *
	 * @group API
	 */
	public function testGetEntitiesByBadTitleNormalized( ) {
		$pageTitle = 'klaijehr qowienx_qopweiu';
		list( $res,, ) = $this->doApiRequest( array(
			'action' => 'wbgetentities',
			'sites' => 'enwiki',
			'titles' => $pageTitle,
			'normalize' => true
		) );

		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities' );

		$keys = array_keys( $res['entities'] );
		$this->assertEquals( 1, count( $keys ), "requesting a single item should return exactly one item entry" );

		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities', $keys[0], 'missing' );

		// The normalization that has been applied should be noted
		$this->assertEquals(
			$pageTitle,
			$res['normalized']['n']['from']
		);

		$this->assertEquals(
			// Normalization in unit tests is actually using Title::getPrefixedText instead of a real API call
			\Title::newFromText( $pageTitle )->getPrefixedText(),
			$res['normalized']['n']['to']
		);
	}

	/**
	 * Testing if we can get all the complete stringified items if we do lookup with multiple ids.
	 *
	 * @group API
	 */
	public function testGetEntitiesMultipleIds() {
		$ids = array();
		foreach( self::$usedHandles as $handle ){
			$ids[] = EntityTestHelper::getId( $handle );
		}

		list( $res,, ) = $this->doApiRequest( array(
			'action' => 'wbgetentities',
			'format' => 'json', // make sure IDs are used as keys
			'ids' => join( '|', $ids ),
		) );

		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities' );
		$this->assertEquals( count( $ids ), count( $res['entities'] ), "the actual number of items differs from the number of requested items" );

		foreach ( $ids as $id ) {
			$this->assertArrayHasKey( $id, $res['entities'], "missing item" );
			$this->assertEquals( $id, $res['entities'][$id]['id'], "bad ID" );
		}
	}

	/**
	 * Testing if we can get all the complete stringified items if we do lookup with multiple site-title pairs.
	 *
	 * @group API
	 */
	public function testGetEntitiesMultipleSiteLinks() {
		$handles = array( 'Berlin', 'London', 'Oslo' );
		$sites = array( 'dewiki', 'enwiki', 'nlwiki' );
		$titles = array( 'Berlin', 'London', 'Oslo' );

		list( $res,, ) = $this->doApiRequest( array(
			'action' => 'wbgetentities',
			'format' => 'json', // make sure IDs are used as keys
			'sites' => join( '|', $sites ),
			'titles' => join( '|', $titles )
		) );

		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities' );
		$this->assertEquals( count( $titles ), count( $res['entities'] ), "the actual number of items differs from the number of requested items" );

		foreach ( $handles as $handle ) {
			$id = EntityTestHelper::getId( $handle );

			$this->assertArrayHasKey( $id, $res['entities'], "missing item" );
			$this->assertEquals( $id, $res['entities'][$id]['id'], "bad ID" );
		}
	}

	function provideLanguages() {
		return array(
			array( 'Berlin', array( 'en', 'de' ) ),
			array( 'Leipzig', array( 'en', 'de' ) ),
			array( 'Leipzig', array( 'fr', 'nl' ) ),
			array( 'London', array( 'nl' ) ),
			array( 'Empty', array( 'nl' ) ),
		);
	}

	/**
	 * @dataProvider provideLanguages
	 */
	function testLanguages( $handle, $languages ) {
		$id = EntityTestHelper::getId( $handle );

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'languages' => join( '|', $languages ),
				'format' => 'json', // make sure IDs are used as keys
				'ids' => $id )
		);

		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities', $id );

		$item = $res['entities'][$id];

		foreach ( $item as $prop => $values ) {
			if ( !is_array( $values ) ) {
				continue;
			}

			if ( $prop === 'sitelinks' ) {
				continue;
			}

			$values = static::flattenArray( $values, 'language', 'value' );

			$this->assertEmpty( array_diff( array_keys( $values ), $languages ),
								"found unexpected language in property $prop: " . var_export( $values, true ) );
		}
	}

	function provideLanguageFallback() {
		return array(
			array(
				'Guangzhou',
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
	 */
	function testLanguageFallback( $handle, $languages, $expectedLabels, $expectedDescriptions ) {
		$id = EntityTestHelper::getId( $handle );

		list($res,,) = $this->doApiRequest(
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

	function provideProps() {
		return array(
			array( 'Berlin', '', array( 'id', 'type' ) ),
			array( 'Berlin', 'labels', array( 'id', 'type', 'labels' ) ),
			array( 'Berlin', 'labels|descriptions', array( 'id', 'type', 'labels', 'descriptions' ) ),
			array( 'Berlin', 'aliases|sitelinks', array( 'id', 'type', 'aliases', 'sitelinks' ) ),

			array( 'Leipzig', '', array( 'id', 'type' ) ),
			array( 'Leipzig', 'labels|descriptions', array( 'id', 'type', 'labels', 'descriptions' ) ),
			array( 'Leipzig', 'labels|aliases', array( 'id', 'type', 'labels' ) ), // aliases are omitted because empty
			array( 'Leipzig', 'sitelinks|descriptions', array( 'id', 'type', 'descriptions' ) ), // sitelinks are omitted because empty

			// TODO: test this for property entities props, e.g. 'datatype'

			array( 'Berlin', 'xyz', false ),
		);
	}

	/**
	 * @dataProvider provideProps
	 */
	function testProps( $handle, $props, $expectedProps ) {
		$id = EntityTestHelper::getId( $handle );

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'props' => $props,
				'format' => 'json', // make sure IDs are used as keys
				'ids' => $id )
		);

		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities', $id );

		if ( $expectedProps === false ) {
			$this->assertArrayHasKey( 'warnings', $res );
			$this->assertArrayHasKey( 'wbgetentities', $res['warnings'] );
		} else {
			$this->assertArrayEquals( $expectedProps, array_keys( $res['entities'][$id] ) );
		}
	}

	function provideSitelinkUrls() {
		return array(
			array( 'Berlin' ),
			array( 'London' ),
		);
	}

	/**
	 * @dataProvider provideSitelinkUrls
	 */
	function testSitelinkUrls( $handle ) {
		$id = EntityTestHelper::getId( $handle );

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'props' => 'sitelinks',
				'format' => 'json', // make sure IDs are used as keys
				'ids' => $id )
		);

		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities', $id, 'sitelinks' );

		foreach ( $res['entities'][$id]['sitelinks'] as $link ) {
			$this->assertArrayNotHasKey( 'url', $link );
		}

		// -------------------------------------------
		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'props' => 'sitelinks|sitelinks/urls',
				'format' => 'json', // make sure IDs are used as keys
				'ids' => $id )
		);

		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities', $id, 'sitelinks' );

		foreach ( $res['entities'][$id]['sitelinks'] as $link ) {
			$this->assertArrayHasKey( 'url', $link, "SiteLinks in the result must have the 'url' key set!" );
			$this->assertNotEmpty( $link['url'], "The value of the 'url' key is not allowed to be empty!" );
		}
	}

	function provideSitelinkSorting() {
		return array(
			array( 'Berlin' ),
			array( 'London' ),
		);
	}

	/**
	 * @dataProvider provideSitelinkSorting
	 */
	function testSitelinkSorting( $handle ) {
		$id = EntityTestHelper::getId( $handle );

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'props' => 'sitelinks',
				'sort' => 'sitelinks',
				'dir' => 'ascending',
				'format' => 'json', // make sure IDs are used as keys
				'ids' => $id )
		);

		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities', $id, 'sitelinks' );
		$last = '';
		foreach ( $res['entities'][$id]['sitelinks'] as $link ) {
			$this->assertArrayHasKey( 'site', $link );
			$this->assertTrue(strcmp( $last, $link['site'] ) <= 0 );
			$last = $link['site'];
		}

		// -------------------------------------------
		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'props' => 'sitelinks',
				'sort' => 'sitelinks',
				'dir' => 'descending',
				'format' => 'json', // make sure IDs are used as keys
				'ids' => $id )
		);

		$this->assertResultSuccess( $res );
		$this->assertResultHasKeyInPath( $res, 'entities', $id, 'sitelinks' );
		$last = 'zzzzzzzz';
		foreach ( $res['entities'][$id]['sitelinks'] as $link ) {
			$this->assertArrayHasKey( 'site', $link );
			$this->assertTrue(strcmp( $last, $link['site'] ) >= 0 );
			$last = $link['site'];
		}
	}

	/**
	 * @dataProvider providerGetItemFormat
	 */
	function testGetItemFormat( $format, $usekeys ) {
		$id = EntityTestHelper::getId( 'Berlin' );

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'format' => $format,
				'ids' => $id )
		);

		if ( $usekeys ) {
			$this->assertResultSuccess( $res );
			$this->assertResultHasKeyInPath( $res, 'entities', $id );
			foreach ( array( 'sitelinks' => 'site', 'alias' => false, 'labels' => 'language', 'descriptions' => 'language' ) as $prop => $field) {
				if ( array_key_exists( $prop, $res['entities'][$id] ) ) {
					foreach ( $res['entities'][$id][$prop] as $key => $value ) {
						$this->assertTrue( is_string( $key ) );
						if ( $field !== false ) {
							$this->assertArrayHasKey( $field, $value );
							$this->assertTrue( is_string( $value[$field] ) );
							$this->assertEquals( $key, $value[$field] );
						}
					}
				}
			}
		}
		else {
			$this->assertResultSuccess( $res );
			$this->assertResultHasKeyInPath( $res, 'entities' );

			$keys = array_keys( $res['entities'] );
			$n = $keys[0];

			foreach ( array( 'sitelinks' => 'site', 'alias' => false, 'labels' => 'language', 'descriptions' => 'language' ) as $prop => $field) {
				if ( array_key_exists( $prop, $res['entities'][$n] ) ) {
					foreach ( $res['entities'][$n][$prop] as $key => $value ) {
						$this->assertTrue( is_string( $key ) );
						if ( $field !== false ) {
							$this->assertArrayHasKey( $field, $value );
							$this->assertTrue( is_string( $value[$field] ) );
						}
					}
				}
			}
		}
	}

	function providerGetItemFormat() {
		$formats = array( 'json', 'jsonfm', 'php', 'phpfm', 'wddx', 'wddxfm', 'xml',
			'xmlfm', 'yaml', 'yamlfm', 'rawfm', 'txt', 'txtfm', 'dbg', 'dbgfm',
			'dump', 'dumpfm', 'none', );
		$calls = array();

		foreach ( $formats as $format ) {
			$calls[] = array( $format, self::usekeys( $format ) );
		}

		return $calls;
	}

	protected static function usekeys( $format ) {
		static $withKeys = false;

		if ( $withKeys === false ) {
			// Which formats to inject keys into, undefined entries are interpreted as true
			// TODO: This array must be patched if awailable formats that does NOT support
			// usekeys are added, changed or removed.
			$withKeys = array(
				'wddx' => false,
				'wddxfm' => false,
				'xml' => false,
				'xmlfm' => false,
			);
		}

		return isset( $withKeys[$format] ) ? $withKeys[$format] : true;
	}

}

