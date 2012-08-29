<?php

namespace Wikibase\Test;
use ApiTestCase, ApiTestUser;
use Wikibase\Item as Item;
use Wikibase\Settings as Settings;
use Wikibase\ItemContent as ItemContent;

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
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
abstract class ApiModifyItemBase extends ApiTestCase {

	protected static $usepost;
	protected static $usetoken;
	protected static $userights;

	protected static $itemInput = null; // items in input format, using handles as keys
	protected static $itemOutput = array(); // items in output format, using handles as keys

	protected static $loginSession = null;
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

			$this->user =  new ApiTestUser(
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
		parent::setUp();

		$this->init();

		static $hasSites = false;

		if ( !$hasSites ) {
			\TestSites::insertIntoDb();
			$hasSites = true;
		}

		//TODO: preserve session and token between calls?!
		self::$loginSession = false;
		self::$token = false;

		$this->initItems();
		$this->setUpComplete = true;
	}

	/**
	 * Initializes the static list of item input structures, using data from makeItemData().
	 * Note that test items are identified by "handles".
	 */
	protected function initItems() {
		if ( self::$itemInput ) {
			return;
		}

		self::$itemInput = array();
		$data = $this->makeItemData();

		foreach ( $data as $item ) {
			self::$itemInput[ $item['handle'] ] = $item;
		}
	}

	/**
	 * Provides the item data that is to be used as input for creating the test environment.
	 * This data is used in particular by createItems().
	 * Note that test items are identified by "handles".
	 */
	function makeItemData() {
		return array(
			array(
				"handle" => "Empty",
			),
			array(
				"handle" => "Berlin",
				"sitelinks" => array(
					"dewiki" => "Berlin",
					"enwiki" => "Berlin",
					"nlwiki" => "Berlin",
					"nnwiki" => "Berlin"
				),
				"labels" => array(
					"de" => "Berlin",
					"en" => "Berlin",
					"no" => "Berlin",
					"nn" => "Berlin"
				),
				"aliases" => array(
					"de"  => array( "Dickes B" ),
					"en"  => array( "Dickes B" ),
					"nl"  => array( "Dickes B" ),
				),
				"descriptions" => array(
					"de"  => "Bundeshauptstadt und Regierungssitz der Bundesrepublik Deutschland.",
					"en"  => "Capital city and a federated state of the Federal Republic of Germany.",
					"no"  => "Hovedsted og delstat og i Forbundsrepublikken Tyskland.",
					"nn"  => "Hovudstad og delstat i Forbundsrepublikken Tyskland."
				)
			),
			array(
				"handle" => "London",
				"sitelinks" => array(
					"enwiki" => "London",
					"dewiki" => "London",
					"nlwiki" => "London",
					"nnwiki" => "London"
				),
				"labels" => array(
					"de" => "London",
					"en" => "London",
					"no" => "London",
					"nn" => "London"
				),
				"aliases" => array(
					"de"  => array( "City of London", "Greater London" ),
					"en"  => array( "City of London", "Greater London" ),
					"nl"  => array( "City of London", "Greater London" ),
				),
				"descriptions" => array(
					"de"  => "Hauptstadt Englands und des Vereinigten Königreiches.",
					"en"  => "Capital city of England and the United Kingdom.",
					"no"  => "Hovedsted i England og Storbritannia.",
					"nn"  => "Hovudstad i England og Storbritannia."
				)
			),
			array(
				"handle" => "Oslo",
				"sitelinks" => array(
					"dewiki" => "Oslo",
					"enwiki" => "Oslo",
					"nlwiki" => "Oslo",
					"nnwiki" => "Oslo"
				),
				"labels" => array(
					"de" => "Oslo",
					"en" => "Oslo",
					"no" => "Oslo",
					"nn" => "Oslo"
				),
				"aliases" => array(
					"de"  => array( "Oslo City" ),
					"en"  => array( "Oslo City" ),
					"nl"  => array( "Oslo City" ),
				),
				"descriptions" => array(
					"de"  => "Hauptstadt der Norwegen.",
					"en"  => "Capital city in Norway.",
					"no"  => "Hovedsted i Norge.",
					"nn"  => "Hovudstad i Noreg."
				)
			),
			array(
				"handle" => "Episkopi",
				"sitelinks" => array(
					"dewiki" => "Episkopi Cantonment",
					"enwiki" => "Episkopi Cantonment",
					"nlwiki" => "Episkopi Cantonment"
				),
				"labels" => array(
					"de" => "Episkopi Cantonment",
					"en" => "Episkopi Cantonment",
					"nl" => "Episkopi Cantonment"
				),
				"aliases" => array(
					"de"  => array( "Episkopi" ),
					"en"  => array( "Episkopi" ),
					"nl"  => array( "Episkopi" ),
				),
				"descriptions" => array(
					"de"  => "Sitz der Verwaltung der Mittelmeerinsel Zypern.",
					"en"  => "The capital of Akrotiri and Dhekelia.",
					"nl"  => "Het bestuurlijke centrum van Akrotiri en Dhekelia."
				)
			),
			array(
				"handle" => "Leipzig",
				"labels" => array(
					"de" => "Leipzig",
				),
				"descriptions" => array(
					"de"  => "Stadt in Sachsen.",
					"en"  => "City in Saxony.",
				)
			),
		);
	}

	/**
	 * Performs a login, if necessary, and returns the resulting session.
	 */
	function login() {
		if ( !$this->isSetUp() ) {
			throw new \MWException( "can't log in before setUp() was run." );
		}

		$this->init();

		if ( self::$loginSession ) {
			return self::$loginSession;
		}

		list($res,,) = $this->doApiRequest( array(
			'action' => 'login',
			'lgname' => self::$users['wbeditor']->username,
			'lgpassword' => self::$users['wbeditor']->password
		) );

		$token = $res['login']['token'];

		list($res,,$session) = $this->doApiRequest(
			array(
				'action' => 'login',
				'lgtoken' => $token,
				'lgname' => self::$users['wbeditor']->username,
				'lgpassword' => self::$users['wbeditor']->password
			),
			null
		);

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

		$re = $this->doApiRequest(
			array(
				'action' => 'wbsetitem',
				'gettoken' => '' ),
			null,
			false,
			self::$users['wbeditor']->user
		);

		self::$token = $re[0]["wbsetitem"]["itemtoken"];
		return self::$token;
	}

	/**
	 * Initializes the test environment with the items defined by makeItemData() by creating these
	 * items in the database.
	 */
	function createItems() {
		if ( self::$itemOutput ) {
			return;
		}

		$this->initItems();
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
			'action' => 'wbsetitem',
			'token' => $token,
		);

		if ( !is_string($data) ) {
			unset( $data['handle'] );

			if ( isset( $data['id'] ) ) {
				$params['id'] = $data['id'];
				unset( $data['id'] );
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

		if ( !isset( $res['success'] ) || !isset( $res['item'] ) ) {
			throw new \MWException( "failed to create item" );
		}

		return $res['item'];
	}

	/**
	 * Returns the item for the given handle, in input format.
	 */
	function getItemInput( $handle ) {
		if ( !is_string( $handle ) ) {
			trigger_error( "bad handle: $handle", E_USER_ERROR );
		}

		$this->initItems();
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
		$this->initItems();

		$handles = array();

		foreach ( self::$itemInput as $handle => $item ) {
			$handles[] = array( $handle );
		}

		return $handles;
	}

	/**
	 * returns the list handles for the well known test items.
	 */
	function getItemHandles() {
		$this->initItems();

		return array_keys( self::$itemInput );
	}

	/**
	 * Loads an item from the database (via an API call).
	 */
	function loadItem( $id ) {
		list($res,,) = $this->doApiRequest(
			array(
				'action' => 'wbgetitems',
				'ids' => $id )
		);

		return $res['items'][$id];
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
	 *
	 * @return array array the flat version of $data
	 */
	public static function flattenArray( $data, $keyField, $valueField, $multiValue = false ) {
		$re = array();

		foreach ( $data as $index => $value ) {
			if ( is_int( $index) && is_array( $value )
				&& isset( $value[$keyField] ) && isset( $value[$valueField] ) ) {

				// found "deep" entry in the array
				$k = $value[ $keyField ];
				$v = $value[ $valueField ];
			} else {
				// found "flat" entry in the array
				$k = $index;
				$v = $value;
			}

			if ( $multiValue ) {
				$re[$k][] = $v;
			} else {
				$re[$k] = $v;
			}
		}

		return $re;
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

		if ( isset( $expected['labels'] ) ) {
			$data = $actual['labels'];

			// find out whether $expected is in "flat" form
			$flat = !isset( $expected['labels'][0] );

			if ( $flat ) { // convert to flat form if necessary
				$data = self::flattenArray( $data, 'language', 'value' );
			}

			// keys are significant in flat form
			$this->assertArrayEquals( $expected['labels'], $data, false, $flat );
		}

		if ( isset( $expected['descriptions'] ) ) {
			$data = $actual['descriptions'];

			// find out whether $expected is in "flat" form
			$flat = !isset( $expected['descriptions'][0] );

			if ( $flat ) { // convert to flat form if necessary
				$data = self::flattenArray( $data, 'language', 'value' );
			}

			// keys are significant in flat form
			$this->assertArrayEquals( $expected['descriptions'], $data, false, $flat );
		}

		if ( isset( $expected['sitelinks'] ) ) {
			$data = $actual['sitelinks'];

			// find out whether $expected is in "flat" form
			$flat = !isset( $expected['sitelinks'][0] );

			if ( $flat ) { // convert to flat form if necessary
				$data = self::flattenArray( $data, 'site', 'title' );
			}

			// keys are significant in flat form
			$this->assertArrayEquals( $expected['sitelinks'], $data, false, $flat );
		}

		if ( isset( $expected['aliases'] ) ) {
			$data = $actual['aliases'];

			// find out whether $expected is in "flat" form
			$flat = !isset( $expected['aliases'][0] );

			if ( $flat ) { // convert to flat form if necessary
				$data = self::flattenArray( $data, 'language', 'value', true );
			}

			// keys are significant in flat form
			$this->assertArrayEquals( $expected['aliases'], $data, false, $flat );
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

}
