<?php

namespace Wikibase\Test\Api;
use ApiTestCase;
use Wikibase\Settings;
use Wikibase\Test\PermissionsHelper;

/**
 * Tests for permission handling in the Wikibase API.
 *
 * This file produce errors if run standalone.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler <daniel.kinzler@wikimedia.de>
 * 
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group PermissionsTest
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
class PermissionsTest extends WikibaseApiTestCase {

	protected $permissions;
	protected $old_user;

	private static $hasSetup;

	function setUp() {
		global $wgGroupPermissions, $wgUser;

		parent::setUp();

		if( !isset( self::$hasSetup ) ){
			$this->initTestEntities( array( 'Oslo' ) );
		}
		self::$hasSetup = true;

		$this->permissions = $wgGroupPermissions;
		$this->old_user = $wgUser;
	}

	function tearDown() {
		global $wgGroupPermissions;
		global $wgUser;

		$wgGroupPermissions = $this->permissions;

		if ( $this->old_user ) { // should not be null, but sometimes, it is
			$wgUser = $this->old_user;
		}

		if ( $wgUser ) { // should not be null, but sometimes, it is
			// reset rights cache
			$wgUser->addGroup( "dummy" );
			$wgUser->removeGroup( "dummy" );
		}

		parent::tearDown();
	}

	function doPermissionsTest( $action, $params, $permissions = array(), $expectedError = null, array $restore = array() ) {
		global $wgUser;

		PermissionsHelper::applyPermissions( $permissions );

		try {
			if ( !Settings::get( 'apiInDebug' ) || Settings::get( 'apiDebugWithTokens', false ) ) {
				$params[ 'token' ] = $wgUser->getEditToken();
			}

			$params[ 'action' ] = $action;
			list( $re, , ) = $this->doApiRequest( $params, null, false, $wgUser );

			if ( $expectedError == null ) {
				$this->assertArrayHasKey( 'success', $re, 'API call must report success.' );
				$this->assertEquals( '1', $re['success'], 'API call should have succeeded.' );
			} else {
				$this->fail( 'API call should have failed with a permission error!' );
			}
		} catch ( \UsageException $ex ) {
			if ( $expectedError !== true ) {
				$this->assertEquals( $expectedError, $ex->getCodeString(), 'API did not return expected error code. Got error message ' . $ex );
			}
		}
	}

	function provideReadPermissions() {
		return array(
			array( //0
				null, // normal permissions
				null // no error
			),

			array( //1
				array( // permissions
					'*'    => array( 'read' => false ),
					'user' => array( 'read' => false )
				),
				'readapidenied' // error
			),
		);
	}

	function provideEditPermissions() {
		return array_merge( $this->provideReadPermissions(), array(
			array( //2
				array( // permissions
					'*'    => array( 'edit' => false ),
					'user' => array( 'edit' => false )
				),
				'permissiondenied' // error
			),

			array( //3
				array( // permissions
					'*'    => array( 'writeapi' => false ),
					'user' => array( 'writeapi' => false )
				),
				'writeapidenied' // error
			),

			array( //4
				array( // permissions
					'*'    => array( 'read' => false ),
					'user' => array( 'read' => false )
				),
				'readapidenied' // error
			),
		) );
	}


	function provideGetEntitiesPermissions() {
		$permissions = $this->provideReadPermissions();
		return $permissions;
	}

	/**
	 * @dataProvider provideGetEntitiesPermissions
	 */
	function testGetEntities( $permissions, $expectedError ) {
		$params = array(
			'ids' => EntityTestHelper::getId( 'Oslo' ),
		);

		$this->doPermissionsTest( 'wbgetentities', $params, $permissions, $expectedError, array() );
	}

	function provideAddItemPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = array( //5
			array( // permissions
				'*'    => array( 'createpage' => false ),
				'user' => array( 'createpage' => false )
			),
			'permissiondenied' // error
		);


		$permissions[] = array( //6
			array( // permissions
				'*'    => array( 'item-create' => false ),
				'user' => array( 'item-create' => false )
			),
			'permissiondenied' // error
		);

		return $permissions;
	}

	/**
	 * @dataProvider provideAddItemPermissions
	 */
	function testAddItem( $permissions, $expectedError ) {
		$itemData = array(
			'labels' => array("en" => array( "language" => 'en', "value" => 'Test' ) ),
		);

		$params = array(
			'data' => \FormatJson::encode( $itemData ),
			'new' => 'item',
		);

		$this->doPermissionsTest( 'wbeditentity', $params, $permissions, $expectedError, array() );
	}

	function provideSetSiteLinkPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = array( #5
			array( # permissions
				'*'    => array( 'sitelink-update' => false ),
				'user' => array( 'sitelink-update' => false )
			),
			'permissiondenied' # error
		);

		return $permissions;
	}

	/**
	 * @dataProvider provideSetSiteLinkPermissions
	 */
	function testSetSiteLink( $permissions, $expectedError ) {
		$params = array(
			'id' => EntityTestHelper::getId( 'Oslo' ),
			'linksite' => 'enwiki',
			'linktitle' => 'Oslo',
		);

		$this->doPermissionsTest( 'wbsetsitelink', $params, $permissions, $expectedError, array( "Oslo" ) );
	}

	function provideSetLabelPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = array( //5
			array( // permissions
				'*'    => array( 'label-update' => false ),
				'user' => array( 'label-update' => false )
			),
			'permissiondenied' // error
		);

		return $permissions;
	}

	/**
	 * @dataProvider provideSetLabelPermissions
	 */
	function testSetLabel( $permissions, $expectedError ) {
		$params = array(
			'id' => EntityTestHelper::getId( 'Oslo' ),
			'language' => 'de',
			'value' => 'Oslo',
		);

		$this->doPermissionsTest( 'wbsetlabel', $params, $permissions, $expectedError, array( "Oslo" ) );
	}

	function provideSetDescriptionPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = array( //5
			array( // permissions
				'*'    => array( 'description-update' => false ),
				'user' => array( 'description-update' => false )
			),
			'permissiondenied' // error
		);

		return $permissions;
	}

	/**
	 * @dataProvider provideSetDescriptionPermissions
	 */
	function testSetDescription( $permissions, $expectedError ) {
		$params = array(
			'id' => EntityTestHelper::getId( 'Oslo' ),
			'language' => 'en',
			'value' => 'Capitol of Norway',
		);

		$this->doPermissionsTest( 'wbsetdescription', $params, $permissions, $expectedError, array( "Oslo" ) );
	}

}
