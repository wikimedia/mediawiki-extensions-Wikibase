<?php

namespace Wikibase;

/**
 * Interface for change handling. Whenever a change is detected,
 * it should be fed to this interface which then takes care of
 * notifying all handlers.
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
class ChangeHandler {

	/**
	 * Returns the global instance of the ChangeHandler interface.
	 *
	 * @since 0.1
	 *
	 * @return ChangeHandler
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new static();
		}

		return $instance;
	}

	/**
	 * Handle the provided changes.
	 *
	 * @since 0.1
	 *
	 * @param Change[] $changes
	 */
	public function handleChanges( array $changes, $batch = false ) {
		wfRunHooks( 'WikibasePollBeforeHandle', array( $changes ) );

		if ( $batch === true ) {
			wfRunHooks( 'WikibasePollHandle', array( $changes ) );
		} else {
			foreach ( $changes as $change ) {
				wfRunHooks( 'WikibasePollHandle', array( $change ) );
			}
		}

		wfRunHooks( 'WikibasePollAfterHandle', array( $changes ) );
	}

}
