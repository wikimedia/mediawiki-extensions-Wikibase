<?php

namespace Wikibase\Test;
use ApiTestCase, ApiTestUser;
use Wikibase\Settings as Settings;

/**
 * Tests for permission handling in the Wikibase API.
 *
 * This file produce errors if run standalone.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler <daniel.kinzler@wikimedia.de>
 * 
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group ApiPermissionsTest
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
class ApiPermissionsTest extends ApiModifyItemBase {

	protected $permissions;
	protected $user;

	function setUp() {
		global $wgGroupPermissions, $wgUser;

		parent::setUp();

		$this->permissions = $wgGroupPermissions;
		$this->user = $wgUser;

		\TestSites::insertIntoDb();

		# HACK!!
		#$sites = \Wikibase\Sites::singleton( true );
		#$sites->loadSites();
	}

	function tearDown() {
		global $wgGroupPermissions;
		global $wgUser;

		$wgGroupPermissions = $this->permissions;
		$wgUser = $this->user;

		# reset rights cache
		$wgUser->addGroup( "dummy" );
		$wgUser->removeGroup( "dummy" );

		parent::tearDown();
	}

	function getTokens() {
		$re = $this->getTokenList( self::$users['sysop'] );
		return $re[0];
	}

	function doPermissionsTest( $action, $params, $permissions, $expectedError ) {
		global $wgUser;

		self::applyPermissions( $permissions );

		$token = null;

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

	function provideEditPermissions() {
		return array(
			array( #0
				null, # normal permissions
				null # no error
			),

			array( #2
				array( # permissions
					'*'    => array( 'edit' => false ),
					'user' => array( 'edit' => false )
				),
				'cant-edit' # error
			),

			array( #3
				array( # permissions
					'*'    => array( 'writeapi' => false ),
					'user' => array( 'writeapi' => false )
				),
				'writeapidenied' # error
			),

			array( #4
				array( # permissions
					'*'    => array( 'read' => false ),
					'user' => array( 'read' => false )
				),
				'readapidenied' # error
			),
		);
	}

	function provideAddItemPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = array( #4
			array( # permissions
				'*'    => array( 'createpage' => false ),
				'user' => array( 'createpage' => false )
			),
			'cant-edit' # error
		);


		$permissions[] = array( #5
			array( # permissions
				'*'    => array( 'item-create' => false ),
				'user' => array( 'item-create' => false )
			),
			'cant-edit' # error
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

		$json = new \Services_JSON();

		$params = array(
			'data' => $json->encode( $itemData ),
		);

		$this->doPermissionsTest( 'wbsetitem', $params, $permissions, $expectedError );
	}

	function provideSetSiteLinkPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = array( #4
			array( # permissions
				'*'    => array( 'sitelink-update' => false ),
				'user' => array( 'sitelink-update' => false )
			),
			'cant-edit' # error
		);

		return $permissions;
	}

	/**
	 * @dataProvider provideSetSiteLinkPermissions
	 */
	function testSetSiteLink( $permissions, $expectedError ) {
		#XXX: hack: clear tables first. This may create database inconsistencies.
		#TODO: Use $this->tables_used *everywhere*, so each test cleans up after itself.

		// TODO: use store
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( $dbw->tableName( 'wb_items_per_site' ), '*', __METHOD__ );

		$params = array(
			'id' => $this->getItemId( "Oslo" ),
			'linksite' => 'enwiki',
			'linktitle' => 'Oslo',
		);

		$this->doPermissionsTest( 'wbsetsitelink', $params, $permissions, $expectedError );
	}

	function provideSetLabelPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = array( #4
			array( # permissions
				'*'    => array( 'label-update' => false ),
				'user' => array( 'label-update' => false )
			),
			'cant-edit' # error
		);

		return $permissions;
	}

	/**
	 * @dataProvider provideSetLabelPermissions
	 */
	function testSetLabel( $permissions, $expectedError ) {
		$params = array(
			'id' => $this->getItemId( "Oslo" ),
			'language' => 'de',
			'value' => 'Oslo',
		);

		$this->doPermissionsTest( 'wbsetlabel', $params, $permissions, $expectedError );
	}

	function provideSetDescriptionPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = array( #4
			array( # permissions
				'*'    => array( 'description-update' => false ),
				'user' => array( 'description-update' => false )
			),
			'cant-edit' # error
		);

		return $permissions;
	}

	/**
	 * @dataProvider provideSetDescriptionPermissions
	 */
	function testSetDescription( $permissions, $expectedError ) {
		$params = array(
			'id' => $this->getItemId( "Oslo" ),
			'language' => 'en',
			'value' => 'Capitol of Norway',
		);

		$this->doPermissionsTest( 'wbsetdescription', $params, $permissions, $expectedError );
	}

}
