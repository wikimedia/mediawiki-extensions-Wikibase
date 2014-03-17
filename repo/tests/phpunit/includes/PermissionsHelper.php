<?php

namespace Wikibase\Test;

/**
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Adam Shorland
 */
class PermissionsHelper {

	/**
	 * Utility function for applying a set of permissions to $wgGroupPermissions.
	 * Automatically resets the rights cache for $wgUser.
	 * This modifies the global $wgGroupPermissions and $wgUser variables.
	 * No measures are taken to restore the original permissions later, this is up to the caller.
	 *
	 * @param $permissions
	 * @param null|array $groups groups to apply to $wgUser. If not given, group
	 * membership is not modified.
	 */
	public static function applyPermissions( $permissions, $groups = null ) {
		global $wgGroupPermissions;
		global $wgUser;

		if ( !$permissions ) {
			return;
		}

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
