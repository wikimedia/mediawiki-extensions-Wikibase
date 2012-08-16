<?php

namespace Wikibase\Test;
use ApiTestCase, ApiTestUser;
use Wikibase\Settings as Settings;

/**
 * Tests for the ApiWikibase class.
 *
 * This testset only checks the validity of the calls and correct handling of tokens and users.
 * Note that we creates an empty database and then starts manipulating testusers.
 *
 * BE WARNED: the tests depend on execution order of the methods and the methods are interdependent,
 * so stuff will fail if you move methods around or make changes to them without matching changes in
 * all methods depending on them.
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
 * @group ApiSetItemTest
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
class ApiSetItemTest extends ApiModifyItemBase {

	function testSetItem() {
		$item = array(
			"sitelinks" => array(
				"dewiki" => "Foo",
				"enwiki" => "Bar",
			),
			"labels" => array(
				"de" => "Foo",
				"en" => "Bar",
			),
			"aliases" => array(
				"de"  => array( "Fuh" ),
				"en"  => array( "Baar" ),
			),
			"descriptions" => array(
				"de"  => "Metasyntaktische Veriable",
				"en"  => "Metasyntactic variable",
			)
		);

		// ---- check failure without token --------------------------
		$this->login();

		if ( self::$usetoken ) {
			try {
				$this->doApiRequest(
					array(
						'action' => 'wbsetitem',
						'reason' => 'Some reason',
						'data' => json_encode( $item ),
					),
					null,
					false,
					self::$users['wbeditor']->user
				);

				$this->fail( "Adding an item without a token should have failed" );
			}
			catch ( \UsageException $e ) {
				$this->assertTrue( ($e->getCodeString() == 'session-failure'), "Expected session-failure, got unexpected exception: $e" );
			}
		}

		// ---- check success with token --------------------------
		$token = $this->getItemToken();

		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbsetitem',
				'reason' => 'Some reason',
				'data' => json_encode( $item ),
				'token' => $token,
			),
			null,
			false,
			self::$users['wbeditor']->user
		);

		$this->assertSuccess( $res, 'item', 'id' );
		$this->assertItemEquals( $item, $res['item'] );

		$id = $res['item']['id'];

		// ---- check failure to set the same item again, without id -----------
		$item['labels'] = array(
			"de" => "Foo X",
			"en" => "Bar Y",
		);

		try {
			list($res,,) = $this->doApiRequest(
				array(
					'action' => 'wbsetitem',
					'reason' => 'Some reason',
					'data' => json_encode( $item ),
					'token' => $token,
				),
				null,
				false,
				self::$users['wbeditor']->user
			);

			$this->fail( "Adding another item with the same sitelinks should have failed" );
		}
		catch ( \UsageException $e ) {
			$this->assertTrue( ($e->getCodeString() == 'save-failed'), "Expected set-sitelink-failed, got unexpected exception: $e" );
		}

		// ---- check success of update with id --------------------------
		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbsetitem',
				'reason' => 'Some reason',
				'data' => json_encode( $item ),
				'token' => $token,
				'id' => $id,
			),
			null,
			false,
			self::$users['wbeditor']->user
		);

		$this->assertSuccess( $res, 'item', 'id' );
		$this->assertSuccess( $res, 'item', 'lastrevid' );
		$this->assertItemEquals( $item, $res['item'] );

		// ---- set the same item again, with with fields in the json that should be ignored-----------
		// these sets of failing data must be merged with an existing item
		$ignoredData = array(
			array( 'length' => 999999 ), // always ignored
			array( 'count' => 999999 ), // always ignored
			array( 'pageid' => 999999 ),
			array( 'ns' => 200 ),
			array( 'title' => 'does-not-exist' ),
			array( 'lastrevid' => 99999999 ),
			array( 'touched' => '2000-01-01T18:05:01Z' ),
		);
		foreach ( $ignoredData as $data ) {
			try {
				list($res,,) = $this->doApiRequest(
					array(
						'action' => 'wbsetitem',
						'reason' => 'Some reason',
						'data' => json_encode( array_merge( $data, $item ) ),
						'token' => $token,
						'id' => $id,
						'exclude' => 'pageid|ns|title|lastrevid|touched'
					),
					null,
					false,
					self::$users['wbeditor']->user
				);
				$this->assertSuccess( $res, 'item', 'id' );
				$this->assertItemEquals( $item, $res['item'] );
			}
			catch ( \UsageException $e ) {
				$this->fail( "Got unexpected exception: $e" );
			}
		}

		// ---- check failure to set the same item again, with illegal field values in the json -----------
		// these sets of failing data must be merged with an existing item
		$failingData = array(
			array( 'pageid' => 999999 ),
			array( 'ns' => 200 ),
			array( 'title' => 'does-not-exist' ),
			array( 'lastrevid' => 99999999 ),
			array( 'touched' => '2000-01-01T18:05:01Z' ),
		);
		foreach ( $failingData as $data ) {
			try {
				list($res,,) = $this->doApiRequest(
					array(
						'action' => 'wbsetitem',
						'reason' => 'Some reason',
						'data' => json_encode( array_merge( $data, $item ) ),
						'token' => $token,
						'id' => $id,
						'exclude' => ''
					),
					null,
					false,
					self::$users['wbeditor']->user
				);
				$this->fail( "Updating the item with wrong pageid should have failed" );
			}
			catch ( \UsageException $e ) {
				$this->assertTrue( ($e->getCodeString() == 'illegal-field'), "Expected illegal-field, got unexpected exception: $e" );
			}
		}

		// ---- check success to set the same item again, with legal field values in the json -----------
		// these sets of failing data must be merged with an existing item
		list($query,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetitems',
				'props' => 'info',
				'ids' => $id
			),
			null,
			false,
			self::$users['wbeditor']->user
		);
		$this->assertSuccess( $query, 'items', $id, 'id' );
		$goodData = array(
			array( 'pageid' => $query['items'][$id]['pageid'] ),
			array( 'ns' => $query['items'][$id]['ns'] ),
			array( 'title' => $query['items'][$id]['title'] ),
			array( 'lastrevid' => $query['items'][$id]['lastrevid'] ),
			array( 'touched' => $query['items'][$id]['touched'] ),
		);
		foreach ( $goodData as $data ) {
			try {
				list($res,,) = $this->doApiRequest(
					array(
						'action' => 'wbsetitem',
						'reason' => 'Some reason',
						'data' => json_encode( array_merge( $data, $item ) ),
						'token' => $token,
						'id' => $id,
						'exclude' => ''
					),
					null,
					false,
					self::$users['wbeditor']->user
				);
				$this->assertSuccess( $res, 'item', 'id' );
				$this->assertItemEquals( $item, $res['item'] );
			}
			catch ( \UsageException $e ) {
				$this->fail( "Got unexpected exception: $e" );
			}
		}

		// ---- empty the object -----------
		// these sets of failing data must be merged with an existing item
		foreach ( $failingData as $data ) {
			try {
				list($res,,) = $this->doApiRequest(
					array(
						'action' => 'wbsetitem',
						'reason' => 'Some reason',
						'data' => json_encode( array() ),
						'token' => $token,
						'id' => $id,
						'clear' => true,
						'exclude' => '',
					),
					null,
					false,
					self::$users['wbeditor']->user
				);
				$this->assertSuccess( $res, 'item', 'id' );
				$this->assertItemEquals( array( 'id' => $id ), $res['item'] );
			}
			catch ( \UsageException $e ) {
				$this->fail( "Got unexpected exception: $e" );
			}
		}
	}

	function provideBadData() {
		return array(
			// explicit ID is invalid
			array(
				array(
					"id" => 1234567
				),
				"not-recognized"
			),

			// random stuff is invalid
			array(
				array(
					"foo" => "xyz"
				),
				"not-recognized"
			),

			//-----------------------------------------------

			// aliases have to be one list per language
			array(
				array(
					"aliases" => array( "de" => "foo" )
				),
				"not-recognized-array"
			),

			// labels have to be one value per language
			array(
				array(
					"labels" => array( "de" => array( "foo" ) )
				),
				"not-recognized-string"
			),

			// descriptions have to be one value per language
			array(
				array(
					"descriptions" => array( "de" => array( "foo" ) )
				),
				"not-recognized-string"
			),

			//-----------------------------------------------

			// aliases have to use valid language codes
			array(
				array(
					"aliases" => array( "*" => array( "foo" ) )
				),
				"not-recognized-language"
			),

			// labels have to use valid language codes
			array(
				array(
					"labels" => array( "*" => "foo" )
				),
				"not-recognized-language"
			),

			// descriptions have to use valid language codes
			array(
				array(
					"descriptions" => array( "*" => "foo" )
				),
				"not-recognized-language"
			),

			//-----------------------------------------------

			// aliases have to be an array
			array(
				array(
					"aliases" => 15
				),
				"not-recognized-array"
			),

			// labels have to be an array
			array(
				array(
					"labels" => 15
				),
				"not-recognized-array"
			),

			// descriptions be an array
			array(
				array(
					"descriptions" => 15
				),
				"not-recognized-array"
			),

			//-----------------------------------------------

			// json must be valid
			array(
				'',
				"json-invalid"
			),

			// json must be an object
			array(
				'123', // json_decode *will* decode this as an int!
				"not-recognized-array"
			),

			// json must be an object
			array(
				'"foo"', // json_decode *will* decode this as a string!
				"not-recognized-array"
			),

			// json must be an object
			array(
				'[ "xyz" ]', // json_decode *will* decode this as an indexed array.
				"not-recognized-string"
			),
		);
	}

	/**
	 * @dataProvider provideBadData
	 */
	function testSetItemBadData( $data, $expectedErrorCode ) {
		$token = $this->getItemToken();

		try {
			$this->doApiRequest(
				array(
					'action' => 'wbsetitem',
					'reason' => 'Some reason',
					'data' => is_string( $data ) ? $data : json_encode( $data ),
					'token' => $token,
				),
				null,
				false,
				self::$users['wbeditor']->user
			);

			$this->fail( "Adding item should have failed" );
		}
		catch ( \UsageException $e ) {
			$this->assertTrue( ($e->getCodeString() == $expectedErrorCode), "Expected $expectedErrorCode, got unexpected exception: $e" );
		}
	}

	/**
	 * @group API
	 */
	function testGetToken() {
		if ( !static::$usetoken ) {
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

	function provideSetItemData() {
		return array(
			array( #0: labels
				'Berlin', // handle
				array(    // input
					'labels' => array(
						'de' => '', // remove existing
						'ru' => '', // remove non-existing
						'en' => 'Stuff',  // change existing
						'fr' => 'Berlin', // add new
					)
				),
				array(    // expected
					'labels' => array(
						"en" => "Stuff",
						"no" => "Berlin",
						"nn" => "Berlin",
						"fr" => "Berlin",
					)
				),
			),

			array( #1: descriptions
				'Berlin', // handle
				array(    // input
					'descriptions' => array(
						'de' => '', // remove existing
						'ru' => '', // remove non-existing
						'en' => 'Stuff',   // change existing
						'fr' => 'Bla bla', // add new
					)
				),
				array(    // expected
					'descriptions' => array(
						"en"  => "Stuff",
						"no"  => "Hovedsted og delstat og i Forbundsrepublikken Tyskland.",
						"nn"  => "Hovudstad og delstat i Forbundsrepublikken Tyskland.",
						"fr"  => "Bla bla",
					)
				),
			),

			array( #2: aliases
				'Berlin', // handle
				array(    // input
					'aliases' => array(
						"de" => array(), // remove existing
						"ru" => array(), // remove non-existing
						"en"  => array( "Bla bla" ), // change existing
						"fr"  => array( "Bla bla" ), // add new
					)
				),
				array(    // expected
					'aliases' => array(
						"en"  => array( "Bla bla" ),
						"nl"  => array( "Dickes B" ),
						"fr"  => array( "Bla bla" ),
					)
				),
			),

			array( #3: sitelinks
				'Berlin', // handle
				array(    // input
					'sitelinks' => array(
						'dewiki' => '', // remove existing
						'srwiki' => '', // remove non-existing
						"nnwiki" => "Berlin X", // change existing
						"svwiki" => "Berlin X", // add new
					)
				),
				array(    // expected
					'sitelinks' => array(
						"enwiki" => "Berlin",
						"nlwiki" => "Berlin",
						"nnwiki" => "Berlin X",
						"svwiki" => "Berlin X",
					)
				),
			),
		);
	}

	/**
	 * @dataProvider provideSetItemData
	 */
	function testSetItemData( $handle, $data, $expected = null ) {
		$id = $this->getItemId( $handle );
		$token = $this->getItemToken();

		// wbsetitem ------------------------------------------------------
		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbsetitem',
				'reason' => 'Some reason',
				'data' => json_encode( $data ),
				'token' => $token,
				'id' => $id,
			),
			null,
			false,
			self::$users['wbeditor']->user
		);

		// check return value -------------------------------------------
		$this->assertSuccess( $res, 'item' );
		$item = $res['item'];
		$this->assertSuccess( $res, 'item', 'id' );
		$this->assertSuccess( $res, 'item', 'lastrevid' );

		// check relevant entries
		foreach ( $expected as $key => $exp ) {
			$this->assertArrayHasKey( $key, $item );
			$this->assertArrayEquals( $exp, static::flattenValues( $key, $item[$key] ) );
		}

		// check item in database -------------------------------------------
		$item = $this->loadItem( $id );

		// check relevant entries
		foreach ( $expected as $key => $exp ) {
			$this->assertArrayHasKey( $key, $item );
			$this->assertArrayEquals( $exp, static::flattenValues( $key, $item[$key] ) );
		}

		// cleanup ------------------------------------------------------
		$this->resetItem( $handle );
	}

	static function flattenValues( $prop, $values ) {
		if ( !is_array( $values ) ) {
			return $values;
		} elseif ( $prop == 'sitelinks' ) {
			return self::flattenArray( $values, 'site', 'title' );
		} elseif ( $prop == 'aliases' ) {
			return self::flattenArray( $values, 'language', 'value', true );
		} else {
			return self::flattenArray( $values, 'language', 'value' );
		}
	}


	function testProtectedItem() { //TODO
		$this->markTestIncomplete();
	}

	function testBlockedUser() { //TODO
		$this->markTestIncomplete();
	}

	function testEditPermission() { //TODO
		$this->markTestIncomplete();
	}

}
