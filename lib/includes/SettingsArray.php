<?php

namespace Wikibase;

use OutOfBoundsException;

/**
 * Class representing a collection of settings.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
 *
 * @since 0.4
 *
 * @file
 * @ingroup Settings
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SettingsArray extends \ArrayObject {

	/**
	 * Gets the value of the specified setting.
	 *
	 * @since 0.1
	 *
	 * @param string $settingName
	 *
	 * @throws OutOfBoundsException
	 * @return mixed
	 */
	public function getSetting( $settingName ) {
		if ( !$this->offsetExists( $settingName ) ) {
			throw new OutOfBoundsException( 'Attempt to get non-existing setting "' . $settingName . '"' );
		}

		return $this[$settingName];
	}

	/**
	 * Sets the value of the specified setting.
	 *
	 * @since 0.1
	 *
	 * @param string $settingName
	 * @param mixed $settingValue
	 */
	public function setSetting( $settingName, $settingValue ) {
		$this[$settingName] = $settingValue;
	}

	/**
	 * Returns if the specified settings is set or not.
	 *
	 * @since 0.1
	 *
	 * @param string $settingName
	 *
	 * @return boolean
	 */
	public function hasSetting( $settingName ) {
		return $this->offsetExists( $settingName );
	}

}