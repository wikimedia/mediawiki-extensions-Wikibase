<?php

namespace Wikibase\Test\Api;

use FormatJson;

/**
 * Tests for permission handling in the Wikibase API.
 *
 * This file produce errors if run standalone.
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler <daniel.kinzler@wikimedia.de>
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group PermissionsTest
 * @group BreakingTheSlownessBarrier
 * @group Database
 * @group medium
 */
class PermissionsTest extends PermissionsTestCase {

	public function provideReadPermissions() {
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

	public function provideEditPermissions() {
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

	public function provideGetEntitiesPermissions() {
		$permissions = $this->provideReadPermissions();
		return $permissions;
	}

	/**
	 * @dataProvider provideGetEntitiesPermissions
	 */
	public function testGetEntities( $permissions, $expectedError ) {
		$params = array(
			'ids' => EntityTestHelper::getId( 'Oslo' ),
		);

		$this->doPermissionsTest( 'wbgetentities', $params, $permissions, $expectedError );
	}

	public function provideCreateItemPermissions() {
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
	 * @dataProvider provideCreateItemPermissions
	 */
	public function testCreateItem( $permissions, $expectedError ) {
		$itemData = array(
			'labels' => array("en" => array( "language" => 'en', "value" => 'Test' ) ),
		);

		$params = array(
			'data' => FormatJson::encode( $itemData ),
			'new' => 'item',
		);

		$this->doPermissionsTest( 'wbeditentity', $params, $permissions, $expectedError );
	}

	public function provideSetSiteLinkPermissions() {
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
	public function testSetSiteLink( $permissions, $expectedError ) {
		$params = array(
			'id' => EntityTestHelper::getId( 'Oslo' ),
			'linksite' => 'enwiki',
			'linktitle' => 'Oslo',
		);

		$this->doPermissionsTest( 'wbsetsitelink', $params, $permissions, $expectedError );
	}

	public function provideSetLabelPermissions() {
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
	public function testSetLabel( $permissions, $expectedError ) {
		$params = array(
			'id' => EntityTestHelper::getId( 'Oslo' ),
			'language' => 'de',
			'value' => 'Oslo',
		);

		$this->doPermissionsTest( 'wbsetlabel', $params, $permissions, $expectedError );
	}

	public function provideSetDescriptionPermissions() {
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
	public function testSetDescription( $permissions, $expectedError ) {
		$params = array(
			'id' => EntityTestHelper::getId( 'Oslo' ),
			'language' => 'en',
			'value' => 'Capitol of Norway',
		);

		$this->doPermissionsTest( 'wbsetdescription', $params, $permissions, $expectedError );
	}

	public function provideMergeItemsPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = array( #5
			array( # permissions
				'*'    => array( 'item-merge' => false ),
				'user' => array( 'item-merge' => false )
			),
			'permissiondenied' # error
		);

		return $permissions;
	}

	/**
	 * @dataProvider provideMergeItemsPermissions
	 */
	public function testMergeItems( $permissions, $expectedError ) {
		$params = array(
			'fromid' => EntityTestHelper::getId( 'Oslo' ),
			'toid' => EntityTestHelper::getId( 'Empty' ),
		);

		$this->doPermissionsTest( 'wbmergeitems', $params, $permissions, $expectedError );
	}

}
