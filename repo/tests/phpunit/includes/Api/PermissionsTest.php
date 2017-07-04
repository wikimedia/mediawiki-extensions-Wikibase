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
 * @group BreakingTheSlownessBarrier
 * @group Database
 * @group medium
 */
class PermissionsTest extends PermissionsTestCase {

	public function provideReadPermissions() {
		return [
			[ //0
				null, // normal permissions
				null // no error
			],

			[ //1
				[ // permissions
					'*'    => [ 'read' => false ],
					'user' => [ 'read' => false ]
				],
				'readapidenied' // error
			],
		];
	}

	public function provideEditPermissions() {
		return array_merge( $this->provideReadPermissions(), [
			[ //2
				[ // permissions
					'*'    => [ 'edit' => false ],
					'user' => [ 'edit' => false ]
				],
				'permissiondenied' // error
			],

			[ //3
				[ // permissions
					'*'    => [ 'writeapi' => false ],
					'user' => [ 'writeapi' => false ]
				],
				'writeapidenied' // error
			],

			[ //4
				[ // permissions
					'*'    => [ 'read' => false ],
					'user' => [ 'read' => false ]
				],
				'readapidenied' // error
			],
		] );
	}

	/**
	 * @dataProvider provideReadPermissions
	 */
	public function testGetEntities( array $permissions = null, $expectedError ) {
		$params = [
			'ids' => EntityTestHelper::getId( 'Oslo' ),
		];

		$this->doPermissionsTest( 'wbgetentities', $params, $permissions, $expectedError );
	}

	public function provideCreateEntityPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = [ //5
			[ // permissions
				'*'    => [ 'createpage' => false ],
				'user' => [ 'createpage' => false ]
			],
			'permissiondenied' // error
		];

		return $permissions;
	}

	/**
	 * @dataProvider provideCreateEntityPermissions
	 */
	public function testCreateItem( array $permissions = null, $expectedError ) {
		$itemData = [
			'labels' => [ "en" => [ "language" => 'en', "value" => 'Test' ] ],
		];

		$params = [
			'data' => FormatJson::encode( $itemData ),
			'new' => 'item',
		];

		$this->doPermissionsTest( 'wbeditentity', $params, $permissions, $expectedError );
	}

	public function provideCreatePropertyPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = [ //5
			[ // permissions
				'*'    => [ 'property-create' => false ],
				'user' => [ 'property-create' => false ]
			],
			'permissiondenied' // error
		];

		return $permissions;
	}

	/**
	 * @dataProvider provideCreatePropertyPermissions
	 */
	public function testCreateProperty( array $permissions = null, $expectedError ) {
		$itemData = [
			'labels' => [ "en" => [ "language" => 'en', "value" => 'Testttttttt' ] ],
			'datatype' => 'string',
		];

		$params = [
			'data' => FormatJson::encode( $itemData ),
			'new' => 'property',
		];

		$this->doPermissionsTest( 'wbeditentity', $params, $permissions, $expectedError );
	}

	public function provideItemTermPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = [ //5
			[ // permissions
				'*'    => [ 'item-term' => false ],
				'user' => [ 'item-term' => false ]
			],
			'permissiondenied' // error
		];

		return $permissions;
	}

	/**
	 * @dataProvider provideItemTermPermissions
	 */
	public function testSetLabel( array $permissions = null, $expectedError ) {
		$params = [
			'id' => EntityTestHelper::getId( 'Oslo' ),
			'language' => 'de',
			'value' => 'Oslo',
		];

		$this->doPermissionsTest( 'wbsetlabel', $params, $permissions, $expectedError );
	}

	/**
	 * @dataProvider provideItemTermPermissions
	 */
	public function testSetDescription( array $permissions = null, $expectedError ) {
		$params = [
			'id' => EntityTestHelper::getId( 'Oslo' ),
			'language' => 'en',
			'value' => 'Capitol of Norway',
		];

		$this->doPermissionsTest( 'wbsetdescription', $params, $permissions, $expectedError );
	}

	public function provideMergeItemsPermissions() {
		$permissions = $this->provideEditPermissions();

		$permissions[] = [ #5
			[ # permissions
				'*'    => [ 'item-merge' => false ],
				'user' => [ 'item-merge' => false ]
			],
			'permissiondenied' # error
		];

		return $permissions;
	}

	/**
	 * @dataProvider provideMergeItemsPermissions
	 */
	public function testMergeItems( array $permissions = null, $expectedError ) {
		$params = [
			'fromid' => EntityTestHelper::getId( 'Oslo' ),
			'toid' => EntityTestHelper::getId( 'Empty' ),
		];

		$this->doPermissionsTest( 'wbmergeitems', $params, $permissions, $expectedError );
	}

}
