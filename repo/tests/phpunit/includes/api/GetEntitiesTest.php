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
class GetEntitiesTest extends ModifyEntityTestBase {

	/**
	 * @dataProvider provideEntityHandles
	 */
	function testGetItemById( $handle ) {
		$this->createEntities();

		$item = $this->getEntityOutput( $handle );
		$id = $item['id'];

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'format' => 'json', // make sure IDs are used as keys
				'ids' => $id )
		);

		$this->assertSuccess( $res, 'entities', $id );
		$this->assertEntityEquals( $item,  $res['entities'][$id] );
		$this->assertEquals( 1, count( $res['entities'] ), "requesting a single item should return exactly one item entry" );
		// This should be correct for all items we are testing
		$this->assertEquals( \Wikibase\Item::ENTITY_TYPE,  $res['entities'][$id]['type'] );
		// The following comes from the props=info which is included by default
		// Only check if they are there and seems valid, can't do much more for the moment (or could for title but then we are testing assumptions)
		$this->assertSuccess( $res, 'entities', $id, 'pageid' );
		$this->assertTrue( is_integer( $res['entities'][$id]['pageid'] ) );
		$this->assertTrue( 0 < $res['entities'][$id]['pageid'] );
		$this->assertSuccess( $res, 'entities', $id, 'ns' );
		$this->assertTrue( is_integer( $res['entities'][$id]['ns'] ) );
		$this->assertTrue( 0 <= $res['entities'][$id]['ns'] );
		$this->assertSuccess( $res, 'entities', $id, 'title' );
		$this->assertTrue( is_string( $res['entities'][$id]['title'] ) );
		$this->assertTrue( 0 < strlen( $res['entities'][$id]['title'] ) );
		$this->assertSuccess( $res, 'entities', $id, 'lastrevid' );
		$this->assertTrue( is_integer( $res['entities'][$id]['lastrevid'] ) );
		$this->assertTrue( 0 < $res['entities'][$id]['lastrevid'] );
		$this->assertSuccess( $res, 'entities', $id, 'modified' );
		$this->assertTrue( is_string( $res['entities'][$id]['modified'] ) );
		$this->assertTrue( 0 < strlen( $res['entities'][$id]['modified'] ) );
		$this->assertRegExp( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z$/',
			$res['entities'][$id]['modified'], "should be in ISO 8601 format" );

		//@todo: check non-item
	}

	/**
	 * @dataProvider provideEntityHandles
	 */
	function testGetItemByPrefixedId( $handle ) {
		$this->createEntities();

		$item = $this->getEntityOutput( $handle );
		$id = $item['id'];

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'format' => 'json', // make sure IDs are used as keys
				'ids' => $id )
		);

		$this->assertSuccess( $res, 'entities', $id );
		$this->assertEntityEquals( $item,  $res['entities'][$id] );
	}

	public static function provideGetItemByTitle() {
		$calls = array();
		$handles = static::getEntityHandles();

		foreach ( $handles as $handle ) {
			$item = static::getEntityInput( $handle );

			if ( !isset( $item['sitelinks'] ) ) {
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

		$item = $this->getEntityOutput( $handle );
		$id = $item['id'];

		$this->assertSuccess( $res, 'entities', $id );
		$this->assertEntityEquals( $item,  $res['entities'][$id] );
		$this->assertEquals( 1, count( $res['entities'] ), "requesting a single item should return exactly one item entry" );
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

		$this->assertSuccess( $res, 'entities', $badid, 'missing' );
		$this->assertEquals( 1, count( $res['entities'] ), "requesting a single item should return exactly one item entry" );
	}

	/**
	 * Testing behavior for malformed entity ids.
	 *
	 * @group API
	 */
	public function testGetEntitiesByMalformedId( ) {
		try {
			$badid = 'xyz123+++';
			$this->doApiRequest( array(
				'action' => 'wbgetentities',
				'ids' => $badid,
			) );

			$this->fail( "Expected a usage exception when providing a malformed id" );
		} catch ( \UsageException $ex ) {
			$this->assertTrue( true, "make phpunit happy" );
		}
	}

	/**
	 * Testing if we can get an item using a site that is not part of the group supported for sitelinks.
	 * Note that this makes assumptions about which sitelinks exist in the test items.
	 *
	 * @group API
	 */
	public function testGetEntitiesByBadSite( ) {
		try {
			$this->doApiRequest( array(
				'action' => 'wbgetentities',
				'sites' => 'enwiktionary',
				'titles' => 'Berlin',
			) );

			$this->fail( "expected request to fail" );
		} catch ( \UsageException $ex ) {
			// ok
			$this->assertTrue( true );
		}
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

		$this->assertSuccess( $res, 'entities' );

		$keys = array_keys( $res['entities'] );
		$this->assertEquals( 1, count( $keys ), "requesting a single item should return exactly one item entry" );

		$this->assertSuccess( $res, 'entities', $keys[0], 'missing' );
	}

	/**
	 * Testing if we can get all the complete stringified items if we do lookup with multiple ids.
	 *
	 * @group API
	 */
	public function testGetEntitiesMultipleIds() {
		$handles = $this->getEntityHandles();
		$ids = array_map( array( $this, 'getEntityId' ), $handles );

		list( $res,, ) = $this->doApiRequest( array(
			'action' => 'wbgetentities',
			'format' => 'json', // make sure IDs are used as keys
			'ids' => join( '|', $ids ),
		) );

		$this->assertSuccess( $res, 'entities' );
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

		$this->assertSuccess( $res, 'entities' );
		$this->assertEquals( count( $titles ), count( $res['entities'] ), "the actual number of items differs from the number of requested items" );

		foreach ( $handles as $handle ) {
			$id = $this->getEntityId( $handle );

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
		$this->createEntities();

		$id = $this->getEntityId( $handle );

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'languages' => join( '|', $languages ),
				'format' => 'json', // make sure IDs are used as keys
				'ids' => $id )
		);

		$this->assertSuccess( $res, 'entities', $id );

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
		$this->createEntities();

		$id = $this->getEntityId( $handle );

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'props' => $props,
				'format' => 'json', // make sure IDs are used as keys
				'ids' => $id )
		);

		$this->assertSuccess( $res, 'entities', $id );

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
		$this->createEntities();
		$id = $this->getEntityId( $handle );

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'props' => 'sitelinks',
				'format' => 'json', // make sure IDs are used as keys
				'ids' => $id )
		);

		$this->assertSuccess( $res, 'entities', $id, 'sitelinks' );

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

		$this->assertSuccess( $res, 'entities', $id, 'sitelinks' );

		// TODO: assert the URL attributes are present

		// FIXME: the url attirbutes are not present as the urls are not
		// known due to this test not inserting sites first as it should
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
		$this->createEntities();
		$id = $this->getEntityId( $handle );

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'props' => 'sitelinks',
				'sort' => 'sitelinks',
				'dir' => 'ascending',
				'format' => 'json', // make sure IDs are used as keys
				'ids' => $id )
		);

		$this->assertSuccess( $res, 'entities', $id, 'sitelinks' );
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

		$this->assertSuccess( $res, 'entities', $id, 'sitelinks' );
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
		$this->createEntities();

		$item = $this->getEntityOutput( 'Berlin' );
		$id = $item['id'];

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'format' => $format,
				'ids' => $id )
		);

		if ( $usekeys ) {
			$this->assertSuccess( $res, 'entities', $id );
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
			$this->assertSuccess( $res, 'entities' );

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

