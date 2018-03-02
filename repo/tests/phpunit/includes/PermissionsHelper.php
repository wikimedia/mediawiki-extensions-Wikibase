<?php

namespace Wikibase\Repo\Tests;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Addshore
 */
class PermissionsHelper {

	/**
	 * Utility function for applying a set of permissions to $wgGroupPermissions.
	 * Automatically resets the rights cache for $wgUser.
	 * This modifies the global $wgGroupPermissions and $wgUser variables.
	 * No measures are taken to restore the original permissions later, this is up to the caller.
	 *
	 * @param array[]|null $permissions
	 * @param string[]|null $groups groups to apply to $wgUser. If not given, group
	 * membership is not modified.
	 *
	 * @todo: try to do this without messing with the globals, or at least without hardcoding them.
	 */
	public static function applyPermissions( array $permissions = null, array $groups = null ) {
		global $wgGroupPermissions,
			$wgUser;

		if ( !$permissions ) {
			return;
		}

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
			if ( !empty( $wgGroupPermissions[ $group ] ) ) {
				$wgGroupPermissions[ $group ] = array_merge( $wgGroupPermissions[ $group ], $rights );
			} else {
				$wgGroupPermissions[ $group ] = $rights;
			}
		}

		// reset rights cache
		$wgUser->addGroup( "dummy" );
		$wgUser->removeGroup( "dummy" );
	}

}
