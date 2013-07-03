<?php

namespace Wikibase\Test\Api;
use ApiTestCase;
use Wikibase\Settings;

/**
 * Tests for permission handling in the Wikibase API.
 *
 * This file produce errors if run standalone.
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
class PermissionsTest extends ModifyItemBase {

	protected $permissions;
	protected $old_user;

	function setUp() {
		global $wgGroupPermissions, $wgUser;

		parent::setUp();

		$this->permissions = $wgGroupPermissions;
		$this->old_user = $wgUser;

		\TestSites::insertIntoDb();
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

	function getTokens() {
		$re = $this->getTokenList( self::$users['sysop'] );
		return $re[0];
	}

	function doPermissionsTest( $action, $params, $permissions, $expectedError ) {
		global $wgUser;

		self::applyPermissions( $permissions );

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
				'cant-edit' // error
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
			'ids' => $this->getItemId( "Oslo" ),
		);

		$this->doPermissionsTest( 'wbgetentities', $params, $permissions, $expectedError );
	}

	function provideAddItemPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = array( //5
			array( // permissions
				'*'    => array( 'createpage' => false ),
				'user' => array( 'createpage' => false )
			),
			'cant-edit' // error
		);


		$permissions[] = array( //6
			array( // permissions
				'*'    => array( 'item-create' => false ),
				'user' => array( 'item-create' => false )
			),
			'cant-edit' // error
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

		$this->doPermissionsTest( 'wbeditentity', $params, $permissions, $expectedError );
	}

	function provideSetSiteLinkPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = array( #5
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
		$dbw->delete( 'wb_items_per_site', '*', __METHOD__ );

		$params = array(
			'id' => $this->getItemId( "Oslo" ),
			'linksite' => 'enwiki',
			'linktitle' => 'Oslo',
		);

		$this->doPermissionsTest( 'wbsetsitelink', $params, $permissions, $expectedError );
	}

	function provideSetLabelPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = array( //5
			array( // permissions
				'*'    => array( 'label-update' => false ),
				'user' => array( 'label-update' => false )
			),
			'cant-edit' // error
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

		$permissions[] = array( //5
			array( // permissions
				'*'    => array( 'description-update' => false ),
				'user' => array( 'description-update' => false )
			),
			'cant-edit' // error
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

	function provideSetClaimPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = array( //5
			array( // permissions
				'*'    => array( 'claim-update' => false ),
				'user' => array( 'claim-update' => false )
			),
			'cant-edit' // error
		);

		return $permissions;
	}

}
