<?php

namespace Wikibase\Test;
use ApiTestCase, ApiTestUser;
use Wikibase\Settings as Settings;

/**
 * Tests for permission handling in the Wikibase API.
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

		\MW\Test\SitesTest::insertSitesForTests();

		# HACK!!
		#$sites = \MW\Sites::singleton( true );
		#$sites->loadSites();
	}

	function tearDown() {
		global $wgGroupPermissions;

		$wgGroupPermissions = $this->permissions;

		parent::tearDown();
	}

	function getTokens() {
		$re = $this->getTokenList( self::$users['sysop'] );
		return $re[0];
	}

	function applyPermissions( $permissions ) {
		global $wgGroupPermissions;

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

		# reset rights cache
		$this->user->addGroup( "dummy" );
		$this->user->removeGroup( "dummy" );
	}

	function doPermissionsTest( $action, $params, $permissions, $expectedError ) {
		$this->applyPermissions( $permissions );

		$token = null;

		try {
			if ( !Settings::get( 'apiInDebug' ) || Settings::get( 'apiDebugWithTokens', false ) ) {
				$params[ 'token' ] = $this->user->getEditToken();
			}

			$params[ 'action' ] = $action;

			list( $re, , ) = $this->doApiRequest( $params, null, false, $this->user );

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
				'*'    => array( 'item-add' => false ),
				'user' => array( 'item-add' => false )
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
			'label' => array( 'en' => 'Test' ),
		);

		$json = new \Services_JSON();

		$params = array(
			'item' => 'add',
			'data' => $json->encode( $itemData ),
		);

		$this->doPermissionsTest( 'wbsetitem', $params, $permissions, $expectedError );
	}

	function provideLinkSiteAddPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = array( #4
			array( # permissions
				'*'    => array( 'site-link-add' => false ),
				'user' => array( 'site-link-add' => false )
			),
			'cant-edit' # error
		);

		return $permissions;
	}

	/**
	 * @dataProvider provideLinkSiteAddPermissions
	 */
	function testLinkSiteAdd( $permissions, $expectedError ) {
		#XXX: hack: clear tables first. This may create database inconsistencies.
		#TODO: Use $this->tables_used *everywhere*, so each test cleans up after itself.
		$dbw = wfGetDB( DB_MASTER );
		$dbw->query( 'TRUNCATE TABLE ' . $dbw->tableName( 'wb_items_per_site' ) );

		$params = array(
			'id' => self::$itemContent->getItem()->getID(),
			'link' => 'add',
			'linksite' => 'enwiki',
			'linktitle' => 'Oslo',
		);

		$this->doPermissionsTest( 'wblinksite', $params, $permissions, $expectedError );
	}

	function provideSetLabelPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = array( #4
			array( # permissions
				'*'    => array( 'lang-attr-update' => false ),
				'user' => array( 'lang-attr-update' => false )
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
			'id' => self::$itemContent->getItem()->getID(),
			'language' => 'de',
			'label' => 'Oslo',
		);

		$this->doPermissionsTest( 'wbsetlanguageattribute', $params, $permissions, $expectedError );
	}

}
