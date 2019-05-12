<?php

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use MediaWiki\MediaWikiServices;
use Wikimedia\TestingAccessWrapper;

/**
 * Base class for permissions tests
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler <daniel.kinzler@wikimedia.de>
 * @author Addshore
 */
class PermissionsTestCase extends WikibaseApiTestCase {

	private static $hasSetup;

	protected function setUp() {
		parent::setUp();

		if ( !isset( self::$hasSetup ) ) {
			$this->initTestEntities( [ 'Oslo', 'Empty' ] );
		}
		self::$hasSetup = true;
	}

	/**
	 * Utility function for applying a set of permissions to $wgGroupPermissions.
	 * Automatically resets the rights cache for $wgUser.
	 * This modifies the global $wgGroupPermissions and $wgUser variables, but both will be
	 * automatically restored at the end of the test.
	 *
	 * @param array[]|null $permissions
	 * @param string[]|null $groups groups to apply to $wgUser. If not given, group
	 * membership is not modified.
	 *
	 * @todo try to do this without messing with the globals, or at least without hardcoding them.
	 */
	protected function applyPermissions( array $permissions = null, array $groups = null ) {
		global $wgUser;

		if ( !$permissions ) {
			return;
		}

		$this->setMwGlobals( 'wgUser', clone $wgUser );

		$wgUser->addToDatabase();

		if ( is_array( $groups ) ) {
			$oldGroups = $wgUser->getGroups();
			foreach ( $oldGroups as $group ) {
				$wgUser->removeGroup( $group );
			}

			foreach ( $groups as $group ) {
				$wgUser->addGroup( $group );
			}
		}

		foreach ( $permissions as $group => $rights ) {
			foreach ( $rights as $key => $val ) {
				$this->setGroupPermissions( $group, $key, $val );
			}
		}

		// reset rights cache
		$wgUser->addGroup( "dummy" );
		$wgUser->removeGroup( "dummy" );

		MediaWikiServices::getInstance()->resetServiceForTesting( 'PermissionManager' );
	}

	protected function doPermissionsTest(
		$action,
		array $params,
		array $permissions = null,
		$expectedError = null
	) {
		global $wgUser;

		$this->applyPermissions( $permissions );

		try {
			$params[ 'action' ] = $action;
			$this->doApiRequestWithToken( $params, null, $wgUser );

			if ( $expectedError !== null ) {
				$this->fail( 'API call should have failed with a permission error!' );
			} else {
				// the below is to avoid the tests being marked incomplete
				$this->assertTrue( true );
			}
		} catch ( ApiUsageException $ex ) {
			if ( $expectedError !== true ) {
				$msg = TestingAccessWrapper::newFromObject( $ex )->getApiMessage();
				$this->assertEquals( $expectedError, $msg->getApiCode(),
					'API did not return expected error code. Got error message ' . $ex );
			}
		}
	}

}
