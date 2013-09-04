<?php

namespace Wikibase\Test\Api;

use ApiTestCase;
use TestUser;
use User;
use Wikibase\Settings;

/**
 * Base class for test classes that test the API modules that derive from ApiWikibaseModifyItem.
 *
 * The tests are using "Database" to get its own set of temporal tables.
 * This is nice so we avoid poisoning an existing database.
 *
 * The tests are using "medium" so they are able to run a little longer before they are killed.
 * Without this they will be killed after 1 second, but the setup of the tables takes so long
 * time that the first few tests get killed.
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
 * @author Adam Shorland
 * @author Michał Łazowik
 */
abstract class WikibaseApiTestCase extends ApiTestCase {

	protected static $usepost;
	protected static $usetoken;
	protected static $userights;

	protected static $loginSession = null;
	protected static $loginUser = null;
	protected static $token = null;

	public function setUp() {
		global $wgUser;
		parent::setUp();

		static $isSetup = false;

		self::$usepost = Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithPost' ) : true;
		self::$usetoken = Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens' ) : true;
		self::$userights = Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithRights' ) : true;

		ApiTestCase::$users['wbeditor'] = new TestUser(
			'Apitesteditor',
			'Api Test Editor',
			'api_test_editor@example.com',
			array( 'wbeditor' )
		);

		$wgUser = self::$users['wbeditor']->user;

		if ( !$isSetup ) {
			\TestSites::insertIntoDb();

			$this->login();

			$isSetup = true;
		}

		//TODO: preserve session and token between calls?!
		self::$loginSession = false;
		self::$token = false;
	}

	/**
	 * Performs a login, if necessary, and returns the resulting session.
	 */
	function login( $user = 'wbeditor' ) {
		self::doLogin( $user );
		self::$loginUser = self::$users[ $user ];
		return self::$loginSession;
	}

	/**
	 * Gets an entity edit token.
	 */
	function getToken( $type = 'edittoken' ) {
		$tokens = self::getTokenList( self::$loginUser );
		return $tokens[$type];
	}

	/**
	 *  Appends an edit token to a request
	 */
	function doApiRequestWithToken( array $params, array $session = null, User $user = null ) {
		$params['token'] = $this->getToken();
		return $this->doApiRequest( $params, $session, false, $user );
	}

	function initTestEntities( $handles ){
		$activeHandles = EntityTestHelper::getActiveHandles();

		foreach( $activeHandles as $handle => $id ){
			if( array_key_exists( $handle, $activeHandles ) ){
				$params = EntityTestHelper::getEntityClear( $handle );
				$params['action'] = 'wbeditentity';
				$this->doApiRequestWithToken( $params );
			}
		}

		foreach( $handles as $handle ){
			$params = EntityTestHelper::getEntity( $handle );
			$params['action'] = 'wbeditentity';
			list($res,,) = $this->doApiRequestWithToken( $params );
			EntityTestHelper::registerEntity( $handle, $res['entity']['id'], $res['entity'] );
		}

	}

	/**
	 * Loads an entity from the database (via an API call).
	 */
	function loadEntity( $id ) {
		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'format' => 'json', // make sure IDs are used as keys.
				'ids' => $id )
		);

		return $res['entities'][$id];
	}

	/**
	 * Do the test for exceptions from Api queries.
	 * @param $params array of params for the api query
	 * @param $exception array details of the exception to expect (type,code,message)
	 */
	public function doTestQueryExceptions( $params, $exception ){
		try{
			if( array_key_exists( 'code', $exception ) && $exception['code'] == 'badtoken' ){
				if ( !self::$usetoken ) {
					$this->markTestSkipped( "tokens disabled" );
				}
				$this->doApiRequest( $params );
			} else {
				$this->doApiRequestWithToken( $params );
			}
			$this->fail( "Failed to throw exception, {$exception['type']} " );

		} catch( \Exception $e ){
			/** @var $e \UsageException */ // trick IDEs into not showing errors
			if( array_key_exists( 'type', $exception ) ){
				$this->assertInstanceOf( $exception['type'], $e );
			}
			if( array_key_exists( 'code', $exception ) ){
				$this->assertEquals( $exception['code'], $e->getCodeString() );
			}
			if( array_key_exists( 'message', $exception ) ){
				$this->assertContains( $exception['message'], $e->getMessage() );
			}
		}
	}

	/**
	 * Utility function for converting an array from "deep" (indexed) to "flat" (keyed) structure.
	 * Arrays that already use a flat structure are left unchanged.
	 *
	 * Arrays with a deep structure are expected to be list of entries that are associative arrays,
	 * where which entry has at least the fields given by $keyField and $valueField.
	 *
	 * Arrays with a flat structure are associative and assign values to meaningful keys.
	 *
	 * @param array $data the input array.
	 * @param string $keyField the name of the field in each entry that shall be used as the key in the flat structure
	 * @param string $valueField the name of the field in each entry that shall be used as the value in the flat structure
	 * @param bool $multiValue whether the value in the flat structure shall be an indexed array of values instead of a single value.
	 * @param array $into optional aggregator.
	 *
	 * @return array array the flat version of $data
	 */
	public static function flattenArray( $data, $keyField, $valueField, $multiValue = false, array &$into = null ) {
		if ( $into === null ) {
			$into = array();
		}

		foreach ( $data as $index => $value ) {
			if ( is_array( $value ) ) {
				if ( isset( $value[$keyField] ) && isset( $value[$valueField] ) ) {
					// found "deep" entry in the array
					$k = $value[ $keyField ];
					$v = $value[ $valueField ];
				} elseif ( isset( $value[0] ) && !is_array( $value[0] ) && $multiValue ) {
					// found "flat" multi-value entry in the array
					$k = $index;
					$v = $value;
				} else {
					// found list, recurse
					self::flattenArray( $value, $keyField, $valueField, $multiValue, $into );
					continue;
				}
			} else {
				// found "flat" entry in the array
				$k = $index;
				$v = $value;
			}

			if ( $multiValue ) {
				if ( is_array( $v ) ) {
					$into[$k] = empty( $into[$k] ) ? $v : array_merge( $into[$k], $v );
				} else {
					$into[$k][] = $v;
				}
			} else {
				$into[$k] = $v;
			}
		}

		return $into;
	}

	/**
	 * Compares two entity structures and asserts that they are equal. Only fields present in $expected are considered.
	 * $expected and $actual can both be either in "flat" or in "deep" form, they are converted as needed before comparison.
	 *
	 * @param $expected
	 * @param $actual
	 */
	public function assertEntityEquals( $expected, $actual ) {
		if ( isset( $expected['id'] ) ) {
			$this->assertEquals( $expected['id'], $actual['id'], 'id' );
		}
		if ( isset( $expected['lastrevid'] ) ) {
			$this->assertEquals( $expected['lastrevid'], $actual['lastrevid'], 'lastrevid' );
		}
		if ( isset( $expected['type'] ) ) {
			$this->assertEquals( $expected['type'], $actual['type'], 'type' );
		}

		if ( isset( $expected['labels'] ) ) {
			$data = self::flattenArray( $actual['labels'], 'language', 'value' );
			$exp = self::flattenArray( $expected['labels'], 'language', 'value' );

			// keys are significant in flat form
			$this->assertArrayEquals( $exp, $data, false, true );
		}

		if ( isset( $expected['descriptions'] ) ) {
			$data = self::flattenArray( $actual['descriptions'], 'language', 'value' );
			$exp = self::flattenArray( $expected['descriptions'], 'language', 'value' );

			// keys are significant in flat form
			$this->assertArrayEquals( $exp, $data, false, true );
		}

		if ( isset( $expected['sitelinks'] ) ) {
			foreach( array( 'title', 'badges' ) as $valueField ) {
				$data = self::flattenArray( $actual['sitelinks'], 'site', $valueField );
				$exp = self::flattenArray( $expected['sitelinks'], 'site', $valueField );

				// keys are significant in flat form
				$this->assertArrayEquals( $exp, $data, false, true );
			}
		}

		if ( isset( $expected['aliases'] ) ) {
			$data = self::flattenArray( $actual['aliases'], 'language', 'value', true );
			$exp = self::flattenArray( $expected['aliases'], 'language', 'value', true );

			// keys are significant in flat form
			$this->assertArrayEquals( $exp, $data, false, true );
		}
	}

	/**
	 * Asserts that the given API response represents a successful call.
	 *
	 * @param array $response
	 */
	public function assertResultSuccess( $response ) {
		$this->assertArrayHasKey( 'success', $response, "Missing 'success' marker in response." );
		$this->assertResultHasEntityType( $response );
	}

	/**
	 * Asserts the existence of some path in the result, represented by any additional parameters.
	 * @param array $response
	 * @param param string $path1 first path element (optional)
	 * @param param string $path2 second path element (optional)
	 * @param param $ ...
	 */
	public function assertResultHasKeyInPath( $response ){
		$path = func_get_args();
		array_shift( $path );

		$obj = $response;
		$p = '/';

		foreach ( $path as $key ) {
			$this->assertArrayHasKey( $key, $obj, "Expected key $key under path $p in the response." );

			$obj = $obj[ $key ];
			$p .= "/$key";
		}
	}

	/**
	 * Asserts that the given API response has a valid entity type if the result contains an entity
	 * @param array $response
	 */
	public function assertResultHasEntityType( $response ){

		if ( isset( $response['entity'] ) ) {
			if ( isset( $response['entity']['type'] ) ) {
				$this->assertTrue( \Wikibase\EntityFactory::singleton()->isEntityType( $response['entity']['type'] ), "Missing valid 'type' in response." );
			}
		}
		elseif ( isset( $response['entities'] ) ) {
			foreach ($response['entities'] as $entity) {
				if ( isset( $entity['type'] ) ) {
					$this->assertTrue( \Wikibase\EntityFactory::singleton()->isEntityType( $entity['type'] ), "Missing valid 'type' in response." );
				}
			}
		}

	}

	/**
	 * Asserts that the revision with the given ID has a summary matching $regex
	 *
	 * @param string $regex|array The regex to match, or an array to build a regex from
	 * @param int $revid
	 */
	protected function assertRevisionSummary( $regex, $revid ) {
		if ( is_array( $regex ) ) {
			$r = '';

			foreach ( $regex as $s ) {
				if ( strlen( $r ) > 0 ) {
					$r .= '.*';
				}

				$r .= preg_quote( $s, '!' );
			}

			$regex = "!$r!";
		}

		$rev = \Revision::newFromId( $revid );
		$this->assertNotNull( $rev, "revision not found: $revid" );

		$comment = $rev->getComment();
		$this->assertRegExp( $regex, $comment );
	}
}
