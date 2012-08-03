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
			$this->doApiRequest(
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
		$this->assertItemEquals( $item, $res['item'] );
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
				'123',
				"not-recognized-array"
			),

			// json must be an object
			array(
				'"foo"',
				"not-recognized-array"
			),

			// json must be an object
			array(
				'[ "xyz" ]',
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

	function testChangeLabel() {
		$this->markTestIncomplete();
	}

	function testChangeDescription() {
		$this->markTestIncomplete();
	}

	function testChangeAlias() {
		$this->markTestIncomplete();
	}

	function testChangeSitelink() {
		$this->markTestIncomplete();
	}

	/*
	function testProtectedItem() { //TODO
		$this->markTestIncomplete();
	}

	function testBlockedUser() { //TODO
		$this->markTestIncomplete();
	}

	function testEditPermission() { //TODO
		$this->markTestIncomplete();
	}
	*/

}
