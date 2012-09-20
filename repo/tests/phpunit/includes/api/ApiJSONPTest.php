<?php

namespace Wikibase\Test;
use ApiTestCase, TestUser;

/**
 * Tests for the ApiWikibase class.
 * 
 * This testset only checks the validity of the calls and correct handling of tokens and users.
 * Note that we creates an empty database and then starts manipulating testusers.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * 
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group ApiJSONPTest
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
class ApiJSONPTest extends ApiTestCase {

	protected static $top = 0;

	function setUp() {
		global $wgUser;
		parent::setUp();

		ApiTestCase::$users['wbeditor'] = new TestUser(
				'Apitesteditor',
				'Api Test Editor',
				'api_test_editor@example.com',
				array( 'wbeditor' )
			);
		$wgUser = self::$users['wbeditor']->user;

		// now we have to do the login with the previous user
		$data = $this->doApiRequest( array(
			'action' => 'login',
			'lgname' => self::$users['wbeditor']->username,
			'lgpassword' => self::$users['wbeditor']->password ) );

		$token = $data[0]['login']['token'];

		$this->doApiRequest( array(
			'action' => 'login',
			'lgtoken' => $token,
			'lgname' => self::$users['wbeditor']->username,
			'lgpassword' => self::$users['wbeditor']->password
			),
			$data );
	}
	
	/**
	 * @group API
	 */
	function testSetItemTokenMissing(  ) {
		try {
			$data = $this->doApiRequest(
				array(
					'action' => 'wbsetitem',
					'callback' => 'sometestfunction',
					'gettoken' => ''
					 ),
				null,
				false,
				self::$users['wbeditor']->user
			);
			$this->assertTrue(
				!(isset($data[0]["wbsetitem"]) && isset($data[0]["wbsetitem"]["itemtoken"])),
				"Did find a token and it should not exist"
			);
		}
		catch ( \UsageException $ex ) {
			$this->assertEquals( 'jsonp-token-violation', $ex->getCodeString(), 'API did not return expected error code. Got error message ' . $ex );
		}
	}

	/**
	 * @group API
	 */
	function testSetItemTokenExist(  ) {
		$data = $this->doApiRequest(
			array(
				'action' => 'wbsetitem',
				'gettoken' => ''
				 ),
			null,
			false,
			self::$users['wbeditor']->user
		);
		$this->assertTrue(
			isset($data[0]["wbsetitem"]) && isset($data[0]["wbsetitem"]["itemtoken"]),
			"Did not find a token and it should exist"
		);
	}
	
}
