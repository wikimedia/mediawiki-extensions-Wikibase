<?php

namespace Wikibase\Test\Api;

use ApiTestCase;
use TestUser;
use Wikibase\Settings;

/**
 * Base class for test classes that test the API modules that derive from ApiWikibaseModifyItem.
 *
 * The tests are using "Database" to get its own set of temporal tables.
 * This is nice so we avoid poisoning an existing database.
 *
 * The tests are using "medium" so they are able to run alittle longer before they are killed.
 * Without this they will be killed after 1 second, but the setup of the tables takes so long
 * time that the first few tests get killed.
 *
 * The tests are doing some assumptions on the id numbers. If the database isn't empty when
 * when its filled with test items the ids will most likely get out of sync and the tests will
 * fail. It seems impossible to store the item ids back somehow and at the same time not being
 * dependant on some magically correct solution. That is we could use GetItemId but then we
 * would imply that this module in fact is correct.
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
 */
abstract class ModifyItemBase extends ApiTestCase {

	protected static $usepost;
	protected static $usetoken;
	protected static $userights;

	protected static $itemInput = null; // items in input format, using handles as keys
	protected static $itemOutput = array(); // items in output format, using handles as keys

	protected static $loginSession = null;
	protected static $loginUser = null;
	protected static $token = null;

	protected $user = null;
	protected $setUpComplete = false;

	protected function isSetUp() {
		return $this->setUpComplete;
	}

	protected function init() {
		global $wgUser;

		if ( !$this->user ) {
			self::$usepost = Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithPost' ) : true;
			self::$usetoken = Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens' ) : true;
			self::$userights = Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithRights' ) : true;

			$this->user =  new TestUser(
				'Apitesteditor',
				'Api Test Editor',
				'api_test_editor@example.com',
				array( 'wbeditor' )
			);

			ApiTestCase::$users['wbeditor'] = $this->user;
		}

		$wgUser = $this->user->user;
	}

	public function setUp() {
		global $wgUser;
		parent::setUp();

		$this->setMwGlobals( array( 'wgUser' => $wgUser ) );

		$this->init();

		static $hasSites = false;

		if ( !$hasSites ) {
			\TestSites::insertIntoDb();
			$hasSites = true;
		}

		//TODO: preserve session and token between calls?!
		self::$loginSession = false;
		self::$token = false;

		self::initItems();
		$this->setUpComplete = true;
	}

	/**
	 * Initializes the static list of item input structures, using data from makeItemData().
	 * Note that test items are identified by "handles".
	 */
	protected static function initItems() {
		if ( self::$itemInput ) {
			return;
		}

		self::$itemInput = array();
		$data = self::makeItemData();

		foreach ( $data as $item ) {
			self::$itemInput[ $item['handle'] ] = $item;
		}
	}

	/**
	 * Provides the item data that is to be used as input for creating the test environment.
	 * This data is used in particular by createItems().
	 * Note that test items are identified by "handles".
	 */
	static function makeItemData() {
		return array(
			array(
				"handle" => "Empty",
			),
			array(
				"handle" => "Berlin",
				"sitelinks" => array(
					array( "site" => "dewiki", "title" => "Berlin" ),
					array( "site" => "enwiki", "title" => "Berlin" ),
					array( "site" => "nlwiki", "title" => "Berlin" ),
					array( "site" => "nnwiki", "title" => "Berlin" ),
				),
				"labels" => array(
					array( "language" => "de", "value" => "Berlin" ),
					array( "language" => "en", "value" => "Berlin" ),
					array( "language" => "no", "value" => "Berlin" ),
					array( "language" => "nn", "value" => "Berlin" ),
				),
				"aliases" => array(
					array( array( "language" => "de", "value" => "Dickes B" ) ),
					array( array( "language" => "en", "value" => "Dickes B" ) ),
					array( array( "language" => "nl", "value" => "Dickes B" ) ),
				),
				"descriptions" => array(
					array( "language" => "de", "value" => "Bundeshauptstadt und Regierungssitz der Bundesrepublik Deutschland." ),
					array( "language" => "en", "value" => "Capital city and a federated state of the Federal Republic of Germany." ),
					array( "language" => "no", "value" => "Hovedsted og delstat og i Forbundsrepublikken Tyskland." ),
					array( "language" => "nn", "value" => "Hovudstad og delstat i Forbundsrepublikken Tyskland." ),
				)
			),
			array(
				"handle" => "London",
				"sitelinks" => array(
					array( "site" => "enwiki", "title" => "London" ),
					array( "site" => "dewiki", "title" => "London" ),
					array( "site" => "nlwiki", "title" => "London" ),
					array( "site" => "nnwiki", "title" => "London" ),
				),
				"labels" => array(
					array( "language" => "de", "value" => "London" ),
					array( "language" => "en", "value" => "London" ),
					array( "language" => "no", "value" => "London" ),
					array( "language" => "nn", "value" => "London" ),
				),
				"aliases" => array(
					array(
						array( "language" => "de", "value" => "City of London" ),
						array( "language" => "de", "value" => "Greater London" ),
					),
					array(
						array( "language" => "en", "value" => "City of London" ),
						array( "language" => "en", "value" => "Greater London" ),
					),
					array(
						array( "language" => "nl", "value" => "City of London" ),
						array( "language" => "nl", "value" => "Greater London" ),
					),
				),
				"descriptions" => array(
					array( "language" => "de", "value" => "Hauptstadt Englands und des Vereinigten Königreiches." ),
					array( "language" => "en", "value" => "Capital city of England and the United Kingdom." ),
					array( "language" => "no", "value" => "Hovedsted i England og Storbritannia." ),
					array( "language" => "nn", "value" => "Hovudstad i England og Storbritannia." ),
				)
			),
			array(
				"handle" => "Oslo",
				"sitelinks" => array(
					array( "site" => "dewiki", "title" => "Oslo" ),
					array( "site" => "enwiki", "title" => "Oslo" ),
					array( "site" => "nlwiki", "title" => "Oslo" ),
					array( "site" => "nnwiki", "title" => "Oslo" ),
				),
				"labels" => array(
					array( "language" => "de", "value" => "Oslo" ),
					array( "language" => "en", "value" => "Oslo" ),
					array( "language" => "no", "value" => "Oslo" ),
					array( "language" => "nn", "value" => "Oslo" ),
				),
				"aliases" => array(
					array(
						array( "language" => "no", "value" => "Christiania" ),
						array( "language" => "no", "value" => "Kristiania" ),
					),
					array(
						array( "language" => "nn", "value" => "Christiania" ),
						array( "language" => "nn", "value" => "Kristiania" ),
					),
					array( "language" => "de", "value" => "Oslo City" ),
					array( "language" => "en", "value" => "Oslo City" ),
					array( "language" => "nl", "value" => "Oslo City" ),
				),
				"descriptions" => array(
					array( "language" => "de", "value" => "Hauptstadt der Norwegen." ),
					array( "language" => "en", "value" => "Capital city in Norway." ),
					array( "language" => "no", "value" => "Hovedsted i Norge." ),
					array( "language" => "nn", "value" => "Hovudstad i Noreg." ),
				)
			),
			array(
				"handle" => "Episkopi",
				"sitelinks" => array(
					array( "site" => "dewiki", "title" => "Episkopi Cantonment" ),
					array( "site" => "enwiki", "title" => "Episkopi Cantonment" ),
					array( "site" => "nlwiki", "title" => "Episkopi Cantonment" ),
				),
				"labels" => array(
					array( "language" => "de", "value" => "Episkopi Cantonment" ),
					array( "language" => "en", "value" => "Episkopi Cantonment" ),
					array( "language" => "nl", "value" => "Episkopi Cantonment" ),
				),
				"aliases" => array(
					array( "language" => "de", "value" => "Episkopi" ),
					array( "language" => "en", "value" => "Episkopi" ),
					array( "language" => "nl", "value" => "Episkopi" ),
				),
				"descriptions" => array(
					array( "language" => "de", "value" => "Sitz der Verwaltung der Mittelmeerinsel Zypern." ),
					array( "language" => "en", "value" => "The capital of Akrotiri and Dhekelia." ),
					array( "language" => "nl", "value" => "Het bestuurlijke centrum van Akrotiri en Dhekelia." ),
				)
			),
			array(
				"handle" => "Leipzig",
				"labels" => array(
					array( "language" => "de", "value" => "Leipzig" ),
				),
				"descriptions" => array(
					array( "language" => "de", "value" => "Stadt in Sachsen." ),
					array( "language" => "en", "value" => "City in Saxony." ),
				)
			),
		);
	}

	/**
	 * Performs a login, if necessary, and returns the resulting session.
	 */
	function login( $user = 'wbeditor' ) {
		if ( !$this->isSetUp() ) {
			throw new \MWException( "can't log in before setUp() was run." );
		}

		if ( is_string( $user ) ) {
			$user = self::$users['wbeditor'];
		}

		$this->init();

		if ( self::$loginSession && $user->username == self::$loginUser->username ) {
			return self::$loginSession;
		}

		// we are becoming someone else, need fresh tokens.
		\ApiQueryInfo::resetTokenCache();

		list($res,,) = $this->doApiRequest( array(
			'action' => 'login',
			'lgname' => $user->username,
			'lgpassword' => $user->password
		) );

		$token = $res['login']['token'];

		list(,,$session) = $this->doApiRequest(
			array(
				'action' => 'login',
				'lgtoken' => $token,
				'lgname' => $user->username,
				'lgpassword' => $user->password
			),
			null
		);

		self::$token = null;
		self::$loginUser = $user;
		self::$loginSession = $session;
		return self::$loginSession;
	}

	/**
	 * Gets an item edit token. Returns a cached token if available.
	 */
	function getItemToken() {
		$this->init();

		if ( !self::$usetoken ) {
			return "";
		}

		$this->login();

		if ( self::$token ) {
			return self::$token;
		}

		list($re,,) = $this->doApiRequest(
			array(
				'action' => 'tokens',
				'type' => 'edit' ),
			null,
			false,
			self::$loginUser->user
		);

		return $re['tokens']['edittoken'];
	}

	/**
	 * Initializes the test environment with the items defined by makeItemData() by creating these
	 * items in the database.
	 */
	function createItems() {
		if ( self::$itemOutput ) {
			return;
		}

		self::initItems();
		$token = $this->getItemToken();

		foreach ( self::$itemInput as $item ) {
			$handle = $item['handle'];
			$createdItem = $this->setItem( $item, $token );

			self::$itemOutput[ $handle ] = $createdItem;
		}
	}

	/**
	 * Restores all well known items test in the database to their original state.
	 */
	function resetItems() {
		$this->createItems();
		$token = $this->getItemToken();

		foreach ( self::$itemInput as $handle => $item ) {
			$item['id'] = $this->getItemId( $handle );

			$this->setItem( $item, $token );
		}
	}

	/**
	 * Restores the item with the given handle to its original state
	 */
	function resetItem( $handle ) {
		$item = $this->getItemInput( $handle );
		$item['id'] = $this->getItemId( $handle );

		$token = $this->getItemToken();
		return $this->setItem( $item, $token );
	}

	/**
	 * Creates or updates a single item in the database
	 */
	function setItem( $data, $token ) {
		$params = array(
			'action' => 'wbeditentity',
			'format' => 'json', // make sure IDs are used as keys.
			'token' => $token,
		);

		if ( !is_string($data) ) {
			unset( $data['handle'] );

			if ( isset( $data['id'] ) ) {
				$params['id'] = $data['id'];
				unset( $data['id'] );
			} else {
				$params['new'] = 'item';
			}

			$data = json_encode( $data );
		}

		$params['data'] = $data;

		list( $res,, ) = $this->doApiRequest(
			$params,
			null,
			false,
			self::$users['wbeditor']->user
		);

		if ( !isset( $res['success'] ) || !isset( $res['entity'] ) ) {
			throw new \MWException( "failed to create item" );
		}

		return $res['entity'];
	}

	/**
	 * Returns the item for the given handle, in input format.
	 */
	static function getItemInput( $handle ) {
		if ( !is_string( $handle ) ) {
			trigger_error( "bad handle: $handle", E_USER_ERROR );
		}

		self::initItems();
		return self::$itemInput[ $handle ];
	}

	/**
	 * Returns the item for the given handle, in output format.
	 * Will initialize the database with test items if necessary.
	 */
	function getItemOutput( $handle ) {
		$this->createItems();
		return self::$itemOutput[ $handle ];
	}

	/**
	 * Returns the database id for the given item handle.
	 * Will initialize the database with test items if necessary.
	 */
	function getItemId( $handle ) {
		$item = $this->getItemOutput( $handle );
		return $item['id'];
	}

	/**
	 * data provider for passing each item handle to the test function.
	 */
	function provideItemHandles() {
		self::initItems();

		$handles = array();

		foreach ( self::$itemInput as $handle => $item ) {
			$handles[] = array( $handle );
		}

		return $handles;
	}

	/**
	 * returns the list handles for the well known test items.
	 */
	static function getItemHandles() {
		self::initItems();

		return array_keys( self::$itemInput );
	}

	/**
	 * Loads an item from the database (via an API call).
	 */
	function loadItem( $id ) {
		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetentities',
				'format' => 'json', // make sure IDs are used as keys.
				'ids' => $id )
		);

		return $res['entities'][$id];
	}

	/**
	 * Utility function for applying a set of permissions to $wgGroupPermissions.
	 * Automatically resets the rights cache for $wgUser.
	 * No measures are taken to restore the original permissions later, this is up to the caller.
	 *
	 * @param $permissions
	 */
	public static function applyPermissions( $permissions ) {
		global $wgGroupPermissions;
		global $wgUser;

		if ( !$permissions ) {
			return;
		}

		foreach ( $permissions as $group => $rights ) {
			if ( !empty( $wgGroupPermissions[ $group ] ) ) {
				$wgGroupPermissions[ $group ] = array_merge( $wgGroupPermissions[ $group ], $rights );
			} else {
				$wgGroupPermissions[ $group ] = $rights;
			}
		}

		// reset rights cache
		$wgUser->addGroup( "dummy" );
		$wgUser->removeGroup( "dummy" );
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
	 * Compares two item structures and asserts that they are equal. Only fields present in $expected are considered.
	 * $expected and $actual can both be either in "flat" or in "deep" form, they are converted as needed before comparison.
	 *
	 * @param $expected
	 * @param $actual
	 */
	public function assertItemEquals( $expected, $actual ) {
		if ( isset( $expected['id'] ) ) {
			$this->assertEquals( $expected['id'], $actual['id'] );
		}
		if ( isset( $expected['lastrevid'] ) ) {
			$this->assertEquals( $expected['lastrevid'], $actual['lastrevid'] );
		}
		if ( isset( $expected['type'] ) ) {
			$this->assertEquals( $expected['type'], $actual['type'] );
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
			$data = self::flattenArray( $actual['sitelinks'], 'site', 'title' );
			$exp = self::flattenArray( $expected['sitelinks'], 'site', 'title' );

			// keys are significant in flat form
			$this->assertArrayEquals( $exp, $data, false, true );
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
	 * Optionally, also asserts the existence of some path in the result, represented by any additional parameters.
	 *
	 * @param array $response
	 * @param string $path1 first path element (optional)
	 * @param string $path2 seconds path element (optional)
	 * @param ...
	 */
	public function assertSuccess( $response ) {
		$this->assertArrayHasKey( 'success', $response, "Missing 'success' marker in response." );

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
