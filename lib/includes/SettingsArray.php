<?php

namespace Wikibase;

use ArrayObject;
use Closure;
use OutOfBoundsException;

/**
 * Class representing a collection of settings.
 *
 * @note: settings can be dynamic: if a setting is given as a closure, the closure will be
 *        called to get the actual setting value the first time this setting is retrieved
 *        using getSetting(). The closure is called with the SettingsArray as the only argument.
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
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class SettingsArray extends ArrayObject {

	/**
	 * Gets the value of the specified setting.
	 *
	 * @param string $settingName
	 *
	 * @throws OutOfBoundsException
	 * @return mixed
	 */
	public function getSetting( $settingName ) {
		if ( !$this->offsetExists( $settingName ) && !array_key_exists( $settingName, $this ) ) {
			throw new OutOfBoundsException( 'Attempt to get non-existing setting "' . $settingName . '"' );
		}

		$value = $this[$settingName];

		// Allow closures to be used for deferred evaluation
		// of "magic" (dynamic) defaults.
		if ( $value instanceof Closure ) {
			$value = $value( $this );

			if ( is_object( $value ) ) {
				$logValue = 'instance of ' . get_class( $value );
			} else {
				$logValue = var_export( $value, true );
			}

			wfDebugLog( __CLASS__, __FUNCTION__ . ': setting ' . $settingName . ' was given as a closure, resolve it to ' . $logValue );

			// only eval once, then remember the value
			$this->setSetting( $settingName, $value );
		}

		return $value;
	}

	/**
	 * Sets the value of the specified setting.
	 *
	 * @param string $settingName
	 * @param mixed $settingValue The desired value. If this is a closure, the closure will be
	 *        called to get the actual setting value the first time this setting is retrieved
	 *        using getSetting(). The closure is called with this SettingsArray as the only argument.
	 */
	public function setSetting( $settingName, $settingValue ) {
		$this[$settingName] = $settingValue;
	}

	/**
	 * Returns if the specified settings is set or not.
	 *
	 * @param string $settingName
	 *
	 * @return boolean
	 */
	public function hasSetting( $settingName ) {
		return $this->offsetExists( $settingName );
	}

}
