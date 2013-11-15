<?php

namespace Wikibase\Test\Api;

use UsageException;
use Wikibase\Settings;
use Wikibase\Test\PermissionsHelper;

/**
 * Base class for permissions tests
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler <daniel.kinzler@wikimedia.de>
 * @author Adam Shorland
 */
class PermissionsTestCase extends WikibaseApiTestCase {

	protected $permissions;
	protected $old_user;

	private static $hasSetup;

	public function setUp() {
		global $wgGroupPermissions, $wgUser;

		parent::setUp();

		if( !isset( self::$hasSetup ) ){
			$this->initTestEntities( array( 'Oslo', 'Empty' ) );
		}
		self::$hasSetup = true;

		$this->permissions = $wgGroupPermissions;
		$this->old_user = $wgUser;
	}

	protected function tearDown() {
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

	protected function doPermissionsTest( $action, $params, $permissions = array(), $expectedError = null ) {
		global $wgUser;

		PermissionsHelper::applyPermissions( $permissions );

		try {
			if ( !Settings::get( 'apiInDebug' ) || Settings::get( 'apiDebugWithTokens', false ) ) {
				$params[ 'token' ] = $wgUser->getEditToken();
			}

			$params[ 'action' ] = $action;
			$this->doApiRequest( $params, null, false, $wgUser );

			if ( $expectedError !== null ) {
				$this->fail( 'API call should have failed with a permission error!' );
			}
		} catch ( UsageException $ex ) {
			if ( $expectedError !== true ) {
				$this->assertEquals( $expectedError, $ex->getCodeString(), 'API did not return expected error code. Got error message ' . $ex );
			}
		}
	}

} 