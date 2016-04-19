<?php

namespace Wikibase\Repo\Tests\Api;

use UsageException;
use Wikibase\Test\PermissionsHelper;

/**
 * Base class for permissions tests
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler <daniel.kinzler@wikimedia.de>
 * @author Addshore
 */
class PermissionsTestCase extends WikibaseApiTestCase {

	protected $permissions;
	protected $old_user;

	private static $hasSetup;

	protected function setUp() {
		global $wgGroupPermissions, $wgUser;

		parent::setUp();

		if ( !isset( self::$hasSetup ) ) {
			$this->initTestEntities( array( 'Oslo', 'Empty' ) );
		}
		self::$hasSetup = true;

		$this->permissions = $wgGroupPermissions;
		$this->old_user = clone $wgUser;
	}

	protected function tearDown() {
		global $wgGroupPermissions;
		global $wgUser;

		$wgGroupPermissions = $this->permissions;

		if ( $this->old_user ) { // should not be null, but sometimes, it is
			$wgUser = $this->old_user;
		}

		parent::tearDown();
	}

	protected function doPermissionsTest(
		$action,
		array $params,
		array $permissions = null,
		$expectedError = null
	) {
		global $wgUser, $wgGroupPermissions;

		$this->setMwGlobals( 'wgUser', clone $wgUser );
		$this->setMwGlobals( 'wgGroupPermissions', $wgGroupPermissions );
		PermissionsHelper::applyPermissions( $permissions );

		try {
			$params[ 'action' ] = $action;
			$this->doApiRequestWithToken( $params, null, $wgUser );

			if ( $expectedError !== null ) {
				$this->fail( 'API call should have failed with a permission error!' );
			} else {
				// the below is to avoid the tests being marked incomplete
				$this->assertTrue( true );
			}
		} catch ( UsageException $ex ) {
			if ( $expectedError !== true ) {
				$this->assertEquals( $expectedError, $ex->getCodeString(),
					'API did not return expected error code. Got error message ' . $ex );
			}
		}
	}

}
