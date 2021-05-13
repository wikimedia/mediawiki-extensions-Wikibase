<?php

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use Wikimedia\TestingAccessWrapper;

/**
 * Base class for permissions tests
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler <daniel.kinzler@wikimedia.de>
 * @author Addshore
 */
class PermissionsTestCase extends WikibaseApiTestCase {

	/** @var bool */
	private static $hasSetup;

	protected function setUp(): void {
		parent::setUp();

		if ( !isset( self::$hasSetup ) ) {
			$this->initTestEntities( [ 'Oslo', 'Empty' ] );
		}
		self::$hasSetup = true;
	}

	/**
	 * Utility function for applying a set of permissions to $wgGroupPermissions.
	 * Automatically resets the rights cache for $this->user.
	 * This modifies the global $wgGroupPermissions, but it will be
	 * automatically restored at the end of the test.
	 *
	 * @param array[]|null $permissions
	 * @param string[]|null $groups groups to apply to $this->user. If not given, group
	 * membership is not modified.
	 *
	 * @todo try to do this without messing with the globals, or at least without hardcoding them.
	 */
	protected function applyPermissions( array $permissions = null, array $groups = null ) {
		$userGroupManager = $this->getServiceContainer()->getUserGroupManager();

		if ( !$permissions ) {
			return;
		}

		$this->user->addToDatabase();

		if ( is_array( $groups ) ) {
			$oldGroups = $userGroupManager->getUserGroups( $this->user );
			foreach ( $oldGroups as $group ) {
				$userGroupManager->removeUserFromGroup( $this->user, $group );
			}

			foreach ( $groups as $group ) {
				$userGroupManager->addUserToGroup( $this->user, $group );
			}
		}

		foreach ( $permissions as $group => $rights ) {
			foreach ( $rights as $key => $val ) {
				$this->setGroupPermissions( $group, $key, $val );
			}
		}

		// reset rights cache
		$userGroupManager->addUserToGroup( $this->user, "dummy" );
		$userGroupManager->removeUserFromGroup( $this->user, "dummy" );

		$this->getServiceContainer()->resetServiceForTesting( 'PermissionManager' );
	}

	protected function doPermissionsTest(
		$action,
		array $params,
		array $permissions = null,
		$expectedError = null
	) {
		$this->applyPermissions( $permissions );

		try {
			$params[ 'action' ] = $action;
			$this->doApiRequestWithToken( $params, null, $this->user );

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
