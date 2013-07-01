<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use Wikibase\EntityId;
use Wikibase\EntityLookup;
use Wikibase\Property;
use Wikibase\PropertyInfoStore;

/**
 * PropertyDataTypeLookup that uses an PropertyInfoStore to find
 * a property's data type ID.
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
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PropertyInfoDataTypeLookup implements PropertyDataTypeLookup {

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $fallbackLookup;

	/**
	 * @var PropertyInfoStore
	 */
	private $infoStore;

	/**
	 * @param PropertyInfoStore      $infoStore
	 * @param PropertyDataTypeLookup $fallbackLookup
	 */
	public function __construct( PropertyInfoStore $infoStore, PropertyDataTypeLookup $fallbackLookup = null ) {
		$this->infoStore = $infoStore;
		$this->fallbackLookup = $fallbackLookup;
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId $propertyId
	 *
	 * @return string
	 */
	public function getDataTypeIdForProperty( EntityId $propertyId ) {
		$dataTypeId = null;
		$info = $this->infoStore->getPropertyInfo( $propertyId );

		if ( $info !== null && isset( $info[PropertyInfoStore::KEY_DATA_TYPE] ) ) {
			$dataTypeId = $info[PropertyInfoStore::KEY_DATA_TYPE];
		}

		if ( $dataTypeId === null && $this->fallbackLookup !== null ) {
			$dataTypeId = $this->fallbackLookup->getDataTypeIdForProperty( $propertyId );

			if ( $dataTypeId !== null ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ': No property info found for '
					. $propertyId . ', but property ID could be retrieved from fallback store!' );

				//TODO: Automatically update the info store?
				//TODO: Suggest to run rebuildPropertyInfo.php
			}
		}

		if ( $dataTypeId === null ) {
			throw new PropertyNotFoundException( $propertyId );
		}

		return $dataTypeId;
	}

}
