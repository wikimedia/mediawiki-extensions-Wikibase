<?php

namespace Wikibase\Repo\Tests\Api;

use FormatJson;

/**
 * Tests for permission handling in the Wikibase API.
 *
 * This file produce errors if run standalone.
 *
 * @license GPL-2.0-or-later
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
		yield 'normal permissions, no error' => [ null, null ];

		yield [
			'permissions' => [
				'*'    => [ 'read' => false ],
				'user' => [ 'read' => false ]
			],
			'error' => 'readapidenied'
		];
	}

	public function provideEditPermissions() {
		yield from $this->provideReadPermissions();

		yield [
			'permissions' => [
				'*'    => [ 'edit' => false ],
				'user' => [ 'edit' => false ]
			],
			'error' => 'permissiondenied'
		];

		yield [
			'permissions' => [
				'*'    => [ 'writeapi' => false ],
				'user' => [ 'writeapi' => false ]
			],
			'error' => 'writeapidenied'
		];

		yield [
			'permissions' => [
				'*'    => [ 'read' => false ],
				'user' => [ 'read' => false ]
			],
			'error' => 'readapidenied'
		];
	}

	/**
	 * @dataProvider provideReadPermissions
	 */
	public function testGetEntities( ?array $permissions, $expectedError ) {
		$params = [
			'ids' => EntityTestHelper::getId( 'Oslo' ),
		];

		$this->doPermissionsTest( 'wbgetentities', $params, $permissions, $expectedError );
	}

	public function provideCreateEntityPermissions() {
		yield from $this->provideEditPermissions();

		yield [
			'permissions' => [
				'*'    => [ 'createpage' => false ],
				'user' => [ 'createpage' => false ]
			],
			'error' => 'permissiondenied'
		];
	}

	/**
	 * @dataProvider provideCreateEntityPermissions
	 */
	public function testCreateItem( ?array $permissions, $expectedError ) {
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
		yield from $this->provideEditPermissions();

		yield [
			'permissions' => [
				'*'    => [ 'property-create' => false ],
				'user' => [ 'property-create' => false ]
			],
			'error' => 'permissiondenied'
		];
	}

	/**
	 * @dataProvider provideCreatePropertyPermissions
	 */
	public function testCreateProperty( ?array $permissions, $expectedError ) {
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
		yield from $this->provideEditPermissions();

		yield [
			'permissions' => [
				'*'    => [ 'item-term' => false ],
				'user' => [ 'item-term' => false ]
			],
			'error' => 'permissiondenied'
		];
	}

	/**
	 * @dataProvider provideItemTermPermissions
	 */
	public function testSetLabel( ?array $permissions, $expectedError ) {
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
	public function testSetDescription( ?array $permissions, $expectedError ) {
		$params = [
			'id' => EntityTestHelper::getId( 'Oslo' ),
			'language' => 'en',
			'value' => 'Capitol of Norway',
		];

		$this->doPermissionsTest( 'wbsetdescription', $params, $permissions, $expectedError );
	}

	public function provideMergeItemsPermissions() {
		yield from $this->provideEditPermissions();

		yield [
			'permissions' => [
				'*'    => [ 'item-merge' => false ],
				'user' => [ 'item-merge' => false ]
			],
			'error' => 'permissiondenied'
		];
	}

	/**
	 * @dataProvider provideMergeItemsPermissions
	 */
	public function testMergeItems( ?array $permissions, $expectedError ) {
		$params = [
			'fromid' => EntityTestHelper::getId( 'Oslo' ),
			'toid' => EntityTestHelper::getId( 'Empty' ),
		];

		$this->doPermissionsTest( 'wbmergeitems', $params, $permissions, $expectedError );
	}

}
