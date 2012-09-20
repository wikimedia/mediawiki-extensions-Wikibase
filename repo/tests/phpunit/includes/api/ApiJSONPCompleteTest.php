<?php

namespace Wikibase\Test;
use ApiTestCase, TestUser;
use Wikibase\Settings as Settings;

/**
 * Tests for the ApiWikibase class.
 * 
 * This testset only checks the validity of the calls and correct handling of tokens.
 * Note that we create an empty database and creates testusers and requests tokens for them
 * and should find (or not find) tokens.
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
 * @group ApiJSONPCompleteTest
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
class ApiJSONPCompleteTest extends ApiTestCase {

	protected static $num = 0;
	protected static $name = 'empty';

	protected static function config($arr) {
		Settings::singleton( true );
		foreach ( $arr as $key => $val ) {
			$egWBSettings[$key] = $val;
		}
	}

	protected static function user() {

		self::$num++;
		self::$name = "wbeditor" . self::$num;

		ApiTestCase::$users[self::$name] = new TestUser(
				'Apitesteditor',
				'Api Test Editor',
				'api_test_editor@example.com',
				array( self::$name )
			);
		return self::$users[self::$name];
	}

	protected function register($user) {
		// now we have to do the login with the previous user
		$data = $this->doApiRequest( array(
			'action' => 'login',
			'lgname' => $user->username,
			'lgpassword' => $user->password ) );

		$token = $data[0]['login']['token'];

		$this->doApiRequest( array(
			'action' => 'login',
			'lgtoken' => $token,
			'lgname' => $user->username,
			'lgpassword' => $user->password
			),
			$data );
	}

	/**
	 * @group API
	 * @dataProvider providerConfig
	 */
	function testSetItemTokenMissing( $missing, $exist, $arr ) {
		global $wgUser;

		self::config( $arr );

		$user = self::user();
		$wgUser = $user->user;

		$this->register( $user );

		try {
			$data = $this->doApiRequest(
				array(
					'action' => 'wbsetitem',
					'format' => 'json',
					'callback' => 'sometestfunction',
					'gettoken' => ''
					 ),
				null,
				false,
				$user->user
			);

			$this->assertTrue(
				$missing === (isset($data[0]["wbsetitem"]) && isset($data[0]["wbsetitem"]["itemtoken"])),
				"Did find a token and it should not exist"
			);
		}
		catch ( \UsageException $ex ) {
			$this->assertEquals( 'jsonp-token-violation', $ex->getCodeString(), 'API did not return expected error code. Got error message ' . $ex );
		}
	}

	/**
	 * @group API
	 * @dataProvider providerConfig
	 */
	function testSetItemTokenExist( $missing, $exist, $arr ) {
		global $wgUser;

		self::config( $arr );

		$user = self::user();
		$wgUser = $user->user;

		$this->register($user);

		$data = $this->doApiRequest(
			array(
				'action' => 'wbsetitem',
				'format' => 'json',
				'gettoken' => ''
				 ),
			null,
			false,
			$user->user
		);
		$this->assertTrue(
			$exist === (isset($data[0]["wbsetitem"]) && isset($data[0]["wbsetitem"]["itemtoken"])),
			"Did not find a token and it should exist"
		);
	}

    public function providerConfig() {
    	$arr = array(
    		array( false, true, array( 'apiInDebug' => false ) )
    	);
    	for ($i = 0; $i <= 1; $i++) {
    		for ($j = 0; $j <= 1; $j++) {
	    		for ($k = 0; $k <= 1; $k++) {
		    		for ($l = 0; $l <= 1; $l++) {
		    			$arr[] = array(
		    				false,
		    				true,
		    				array(
			    				'apiInDebug' => false,
	    						'apiDebugWithWrite' => (bool)$i,
	    						'apiDebugWithPost' => (bool)$j,
		    					'apiDebugWithRights' => (bool)$k,
			    				'apiDebugWithTokens' => (bool)$l
		    				)
		    			);
		    		}
	    		}
    		}
    	}
    	return $arr;
    }

}
