<?php

namespace Wikibase\Test;
use ApiTestCase;
use Wikibase\Settings as Settings;

/**
 * Tests for the ApiWikibase class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group ApiGetItemsTest
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
class ApiGetItemsTest extends ApiModifyItemBase {


	/**
	 * @group API
	 */
	function testGetToken() {
		if ( !self::$usetoken ) {
			$this->markTestSkipped( "tokens disabled" );
			return;
		}

		$data = $this->doApiRequest(
			array(
				'action' => 'wbgetitems',
				'ids' => '23',
				'gettoken' => '' ),
			null,
			false,
			self::$users['wbeditor']->user
		);

		// this should always hold for a logged in user
		// unless we do some additional tricks with the token
		$this->assertEquals(
			34, strlen( $data[0]["wbgetitems"]["itemtoken"] ),
			"The length of the token is not 34 chars"
		);
		$this->assertRegExp(
			'/\+\\\\$/', $data[0]["wbgetitems"]["itemtoken"],
			"The final chars of the token is not '+\\'"
		);
	}

	/**
	 * @dataProvider provideItemHandles
	 */
	function testGetItemById( $handle ) {
		$this->createItems();

		$item = $this->getItemOutput( $handle );
		$id = $item['id'];

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetitems',
				'ids' => $id )
		);

		$this->assertSuccess( $res, 'entities', $id );
		$this->assertItemEquals( $item,  $res['entities'][$id] );
		$this->assertEquals( 1, count( $res['entities'] ), "requesting a single item should return exactly one item entry" );
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
		$this->assertSuccess( $res, 'entities', $id, 'touched' );
		$this->assertTrue( is_string( $res['entities'][$id]['touched'] ) );
		$this->assertTrue( 0 < strlen( $res['entities'][$id]['touched'] ) );
		$this->assertSuccess( $res, 'entities', $id, 'length' );
		$this->assertTrue( is_integer( $res['entities'][$id]['length'] ) );
		$this->assertTrue( 0 < $res['entities'][$id]['length'] );
		$this->assertSuccess( $res, 'entities', $id, 'count' );
		$this->assertTrue( is_integer( $res['entities'][$id]['count'] ) );
		$this->assertTrue( 0 <= $res['entities'][$id]['count'] );
	}

	public function provideGetItemByTitle() {
		$calls = array();
		$handles = $this->getItemHandles();

		foreach ( $handles as $handle ) {
			$item = $this->getItemInput( $handle );

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
			'action' => 'wbgetitems',
			'sites' => $site,
			'titles' => $title,
		) );

		$item = $this->getItemOutput( $handle );
		$id = $item['id'];

		$this->assertSuccess( $res, 'entities', $id );
		$this->assertItemEquals( $item,  $res['entities'][$id] );
		$this->assertEquals( 1, count( $res['entities'] ), "requesting a single item should return exactly one item entry" );
	}

	/**
	 * Testing if we can get missing items if we do lookup with single fake ids.
	 * Note that this makes assumptions about which ids have been assigned.
	 *
	 * @group API
	 */
	public function testGetItemsByBadId( ) {
		$badid =  123456789;
		list( $res,, ) = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'ids' => $badid,
		) );

		$this->assertSuccess( $res, 'entities', $badid, 'missing' );
		$this->assertEquals( 1, count( $res['entities'] ), "requesting a single item should return exactly one item entry" );
	}

	/**
	 * Testing if we can get an item using a site that is not part of the group supported for sitelinks.
	 * Note that this makes assumptions about which sitelinks exist in the test items.
	 *
	 * @group API
	 */
	public function testGetItemsByBadSite( ) {
		try {
			list( $res,, ) = $this->doApiRequest( array(
				'action' => 'wbgetitems',
				'sites' => 'enwiktionary',
				'titles' => 'Berlin',
			) );

			$this->fail( "expected request to fail" );
		} catch ( \UsageException $ex ) {
			// ok
		}
	}

	/**
	 * Testing if we can get missing items if we do lookup with failing titles.
	 * Note that this makes assumptions about which sitelinks they have been assigned.
	 *
	 * @group API
	 */
	public function testGetItemsByBadTitle( ) {
		list( $res,, ) = $this->doApiRequest( array(
			'action' => 'wbgetitems',
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
	public function testGetItemsMultipleIds() {
		$handles = $this->getItemHandles();
		$ids = array_map( array( $this, 'getItemId' ), $handles );

		list( $res,, ) = $this->doApiRequest( array(
			'action' => 'wbgetitems',
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
	public function testGetItemsMultipleSiteLinks() {
		$handles = array( 'Berlin', 'London', 'Oslo' );
		$sites = array( 'dewiki', 'enwiki', 'nlwiki' );
		$titles = array( 'Berlin', 'London', 'Oslo' );

		list( $res,, ) = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'sites' => join( '|', $sites ),
			'titles' => join( '|', $titles )
		) );

		$this->assertSuccess( $res, 'entities' );
		$this->assertEquals( count( $titles ), count( $res['entities'] ), "the actual number of items differs from the number of requested items" );

		foreach ( $handles as $handle ) {
			$id = $this->getItemId( $handle );

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
		$this->createItems();

		$id = $this->getItemId( $handle );

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetitems',
				'languages' => join( '|', $languages ),
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
			array( 'Berlin', '', array( 'id' ) ),
			array( 'Berlin', 'labels', array( 'id', 'labels' ) ),
			array( 'Berlin', 'labels|descriptions', array( 'id', 'labels', 'descriptions' ) ),
			array( 'Berlin', 'aliases|sitelinks', array( 'id', 'aliases', 'sitelinks' ) ),

			array( 'Leipzig', '', array( 'id' ) ),
			array( 'Leipzig', 'labels|descriptions', array( 'id', 'labels', 'descriptions' ) ),
			array( 'Leipzig', 'labels|aliases', array( 'id', 'labels' ) ),
			array( 'Leipzig', 'sitelinks|descriptions', array( 'id', 'descriptions' ) ),

			array( 'Berlin', 'xyz', false ),
		);
	}

	/**
	 * @dataProvider provideProps
	 */
	function testProps( $handle, $props, $expectedProps ) {
		$this->createItems();

		$id = $this->getItemId( $handle );

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetitems',
				'props' => $props,
				'ids' => $id )
		);

		$this->assertSuccess( $res, 'entities', $id );

		if ( $expectedProps === false ) {
			$this->assertArrayHasKey( 'warnings', $res );
			$this->assertArrayHasKey( 'wbgetitems', $res['warnings'] );
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
		$this->createItems();
		$id = $this->getItemId( $handle );

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetitems',
				'props' => 'sitelinks',
				'ids' => $id )
		);

		$this->assertSuccess( $res, 'entities', $id, 'sitelinks' );

		foreach ( $res['entities'][$id]['sitelinks'] as $link ) {
			$this->assertArrayNotHasKey( 'url', $link );
		}

		// -------------------------------------------
		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetitems',
				'props' => 'sitelinks|sitelinks/urls',
				'ids' => $id )
		);

		$this->assertSuccess( $res, 'entities', $id, 'sitelinks' );

		foreach ( $res['entities'][$id]['sitelinks'] as $link ) {
			$this->assertArrayHasKey( 'url', $link );
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
		$this->createItems();
		$id = $this->getItemId( $handle );

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetitems',
				'props' => 'sitelinks',
				'sort' => 'sitelinks',
				'dir' => 'ascending',
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
				'action' => 'wbgetitems',
				'props' => 'sitelinks',
				'sort' => 'sitelinks',
				'dir' => 'descending',
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
		$this->createItems();

		$item = $this->getItemOutput( 'Berlin' );
		$id = $item['id'];

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetitems',
				'format' => $format,
				'ids' => $id )
		);

		$this->assertSuccess( $res, 'entities', $id );
		if ( $usekeys ) {
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
			foreach ( array( 'sitelinks' => 'site', 'alias' => false, 'labels' => 'language', 'descriptions' => 'language' ) as $prop => $field) {
				if ( array_key_exists( $prop, $res['entities'][$id] ) ) {
					foreach ( $res['entities'][$id][$prop] as $key => $value ) {
						$this->assertTrue( is_integer( $key ) );
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
			$calls[] = array( $format, \Wikibase\Api::usekeys( $format ) );
		}
		return $calls;
	}

}

