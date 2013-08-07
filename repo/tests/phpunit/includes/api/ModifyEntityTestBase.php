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
abstract class ModifyEntityTestBase extends ApiTestCase {

	protected static $usepost;
	protected static $usetoken;
	protected static $userights;

	protected static $entityInput = null; // entities in input format, using handles as keys
	protected static $entityOutput = array(); // entities in output format, using handles as keys

	protected static $loginUser = null;

	protected $setUpComplete = false;

	protected function isSetUp() {
		return $this->setUpComplete;
	}

	public function setUp() {
		parent::setUp();

		\TestSites::insertIntoDb();

		self::$usepost = Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithPost' ) : true;
		self::$usetoken = Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens' ) : true;
		self::$userights = Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithRights' ) : true;

		self::$users['wbeditor'] = new TestUser(
			'Apitesteditor',
			'Api Test Editor',
			'api_test_editor@example.com',
			array( 'wbeditor' )
		);

		$this->setMwGlobals( array(
			'wgUser' => self::$users['wbeditor']->user,
		) );

		$this->login();

		self::initEntities();
		$this->setUpComplete = true;
	}

	/**
	 * Initializes the static list of entity input structures, using data from makeEntityData().
	 * Note that test entities are identified by "handles".
	 */
	protected static function initEntities() {
		if ( self::$entityInput ) {
			return;
		}

		self::$entityInput = array();

		foreach ( self::makeEntityData() as $entity ) {
			self::$entityInput[ $entity['handle'] ] = $entity;
		}
	}

	/**
	 * Provides the entity data that is to be used as input for creating the test environment.
	 * This data is used in particular by createEntities().
	 * Note that test Entities are identified by "handles".
	 */
	protected static function makeEntityData() {
		return array(
			array(
				"handle" => "Empty",
				"type" => "item",
			),
			array(
				"handle" => "Berlin",
				"type" => "item",
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
				"type" => "item",
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
				"type" => "item",
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
				"type" => "item",
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
				"type" => "item",
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
		$data = self::doLogin( $user );
		self::$loginUser = self::$users[ $user ];
		return $data;
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

	/**
	 * Initializes the test environment with the entities defined by makeEntityData() by creating these
	 * entities in the database.
	 */
	function createEntities() {
		if ( self::$entityOutput ) {
			return;
		}

		foreach ( self::getEntityInput() as $entity ) {
			$handle = $entity['handle'];
			$createdEntity = $this->setEntity( $entity );

			self::$entityOutput[ $handle ] = $createdEntity;
		}
	}

	/**
	 * Restores the entity with the given handle to its original state
	 * @param $handle of entity to reset
	 * @return mixed
	 */
	function resetEntity( $handle ) {
		$entity = $this->getEntityInput( $handle );
		$entity['id'] = $this->getEntityId( $handle );

		$data = $this->setEntity( $entity );

		self::$entityOutput[ $handle ] = $data;
		return $data;
	}

	/**
	 * Creates or updates a single entity in the database
	 * @param $data array|string data to set as array of json string
	 * @return mixed
	 * @throws \MWException
	 */
	function setEntity( $data ) {
		$params = array(
			'action' => 'wbeditentity',
			'clear' => true,
			'format' => 'json', // make sure IDs are used as keys.
		);

		if ( !is_string($data) ) {
			unset( $data['handle'] );

			if ( isset( $data['id'] ) ) {
				$params['id'] = $data['id'];
				unset( $data['id'] );
			} else {
				$params['new'] = $data['type'];
			}

			unset( $data['type'] );

			$data = json_encode( $data );
		}

		$params['data'] = $data;

		list( $res,, ) = $this->doApiRequestWithToken( $params );

		if ( !isset( $res['success'] ) || !isset( $res['entity'] ) ) {
			throw new \MWException( "failed to create entity" );
		}

		return $res['entity'];
	}

	/**
	 * Returns the entity for the given handle, in input format.
	 */
	static function getEntityInput( $handle = null ) {
		self::initEntities();

		if( $handle === null ){
			return self::$entityInput;
		}

		if ( !is_string( $handle ) ) {
			trigger_error( "bad handle: $handle", E_USER_ERROR );
		}

		return self::$entityInput[ $handle ];
	}

	/**
	 * Returns the entity for the given handle, in output format.
	 * Will initialize the database with test entities if necessary.
	 */
	function getEntityOutput( $handle ) {
		$this->createEntities();
		return self::$entityOutput[ $handle ];
	}

	/**
	 * Returns the database id for the given entity handle.
	 * Will initialize the database with test entities if necessary.
	 */
	function getEntityId( $handle ) {
		$entity = $this->getEntityOutput( $handle );
		return $entity['id'];
	}

	/**
	 * returns the list handles for the well known test entities.
	 */
	static function getEntityHandles() {
		return array_keys( self::getEntityInput() );
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
