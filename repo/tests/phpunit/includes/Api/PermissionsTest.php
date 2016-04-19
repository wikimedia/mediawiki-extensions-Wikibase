<?php

namespace Wikibase\Repo\Tests\Api;

use FormatJson;

/**
 * Tests for permission handling in the Wikibase API.
 *
 * This file produce errors if run standalone.
 *
 * @license GPL-2.0+
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
abstract class PermissionsTest extends PermissionsTestCase {

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

	/**
	 * @dataProvider provideReadPermissions
	 */
	public function testGetEntities( array $permissions = null, $expectedError ) {
		$params = array(
			'ids' => EntityTestHelper::getId( 'Oslo' ),
		);

		$this->doPermissionsTest( 'wbgetentities', $params, $permissions, $expectedError );
	}

	public function provideCreateEntityPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = array( //5
			array( // permissions
				'*'    => array( 'createpage' => false ),
				'user' => array( 'createpage' => false )
			),
			'permissiondenied' // error
		);

		return $permissions;
	}

	/**
	 * @dataProvider provideCreateEntityPermissions
	 */
	public function testCreateItem( array $permissions = null, $expectedError ) {
		$itemData = array(
			'labels' => array( "en" => array( "language" => 'en', "value" => 'Test' ) ),
		);

		$params = array(
			'data' => FormatJson::encode( $itemData ),
			'new' => 'item',
		);

		$this->doPermissionsTest( 'wbeditentity', $params, $permissions, $expectedError );
	}

	public function provideCreatePropertyPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = array( //5
			array( // permissions
				'*'    => array( 'property-create' => false ),
				'user' => array( 'property-create' => false )
			),
			'permissiondenied' // error
		);

		return $permissions;
	}

	/**
	 * @dataProvider provideCreatePropertyPermissions
	 */
	public function testCreateProperty( array $permissions = null, $expectedError ) {
		$itemData = array(
			'labels' => array( "en" => array( "language" => 'en', "value" => 'Testttttttt' ) ),
			'datatype' => 'string',
		);

		$params = array(
			'data' => FormatJson::encode( $itemData ),
			'new' => 'property',
		);

		$this->doPermissionsTest( 'wbeditentity', $params, $permissions, $expectedError );
	}

	public function provideItemTermPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = array( //5
			array( // permissions
				'*'    => array( 'item-term' => false ),
				'user' => array( 'item-term' => false )
			),
			'permissiondenied' // error
		);

		return $permissions;
	}

	/**
	 * @dataProvider provideItemTermPermissions
	 */
	public function testSetLabel( array $permissions = null, $expectedError ) {
		$params = array(
			'id' => EntityTestHelper::getId( 'Oslo' ),
			'language' => 'de',
			'value' => 'Oslo',
		);

		$this->doPermissionsTest( 'wbsetlabel', $params, $permissions, $expectedError );
	}

	/**
	 * @dataProvider provideItemTermPermissions
	 */
	public function testSetDescription( array $permissions = null, $expectedError ) {
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
	public function testMergeItems( array $permissions = null, $expectedError ) {
		$params = array(
			'fromid' => EntityTestHelper::getId( 'Oslo' ),
			'toid' => EntityTestHelper::getId( 'Empty' ),
		);

		$this->doPermissionsTest( 'wbmergeitems', $params, $permissions, $expectedError );
	}

}
