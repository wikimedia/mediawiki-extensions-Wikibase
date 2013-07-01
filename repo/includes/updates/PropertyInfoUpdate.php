<?php

namespace Wikibase;

/**
 * Updates property info entries.
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
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PropertyInfoUpdate extends \DataUpdate {

	/**
	 * Constructor.
	 *
	 * @param Property          $property
	 * @param PropertyInfoStore $store
	 */
	public function __construct( Property $property, PropertyInfoStore $store ) {
		$this->property = $property;
		$this->store = $store;
	}

	/**
	 * Perform the actual work
	 */
	function doUpdate() {
		//XXX: Where to encode the knowledge about how to extract an info array from a Property object?
		//     Should we have a PropertyInfo class? Or can we put this into the Property class?

		$info = array(
			PropertyInfoStore::KEY_DATA_TYPE => $this->property->getDataTypeId()
		);

		$id = $this->property->getId();
		$oldInfo = $this->store->getPropertyInfo( $id );

		if ( $oldInfo !== $info ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': property info for ' . $id . ' changed, updating' );
			$this->store->setPropertyInfo( $id, $info );
		} else {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': property info for ' . $id . ' didn\'t change, skipping update' );
		}
	}
}
