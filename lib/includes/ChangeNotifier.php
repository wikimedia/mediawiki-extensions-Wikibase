<?php

namespace Wikibase;

/**
 * Interface for change notification.
 * Whenever a change is made, it should be fed to this interface
 * so the appropriate notification tasks can be created and run.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ChangeNotifier {

	/**
	 * Handles the provided change.
	 *
	 * @since 0.1
	 *
	 * @param Change $change
	 *
	 * @return \Status
	 */
	public function handleChange( Change $change ) {
		return $this->handleChanges( array( $change ) );
	}

	/**
	 * Handles the provided changes.
	 *
	 * @since 0.1
	 *
	 * @param Change[] $changes
	 *
	 * @return \Status
	 */
	public function handleChanges( array $changes ) {
		if ( !Settings::get( 'useChangesTable' ) ) {
			return \Status::newGood( false );
		}

		foreach ( $changes as $change ) {
			//XXX: the Change interface does not define save().
			$change->save();
		}

		return \Status::newGood( true );
	}

}