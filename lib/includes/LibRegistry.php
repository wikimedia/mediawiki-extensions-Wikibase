<?php

namespace Wikibase;
use DataTypes\DataTypeFactory;

/**
 * Application registry for Wikibase Lib.
 *
 * NOTE:
 * This application registry is a workaround for design problems in existing code.
 * It should only be used to improve existing usage of code and ideally just be
 * a stepping stone towards using proper dependency injection where possible.
 * This means you should be very careful when adding new components to the registry.
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
 * @since 0.4
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LibRegistry {

	/**
	 * @since 0.4
	 *
	 * @var \Settings
	 */
	protected $settings;

	/**
	 * @since 0.4
	 *
	 * @param \Settings $settings
	 */
	public function __construct( \Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @since 0.4
	 *
	 * @return DataTypeFactory
	 */
	public function getDataTypeFactory() {
		global $wgDataTypes;

		$dataTypes = array_intersect_key(
			$wgDataTypes,
			array_flip( $this->settings->getSetting( 'dataTypes' ) )
		);

		return new DataTypeFactory( $dataTypes );
	}

	// Do not add new stuff here without reading the notice at the top first.

}