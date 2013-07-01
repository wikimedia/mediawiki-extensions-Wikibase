<?php

namespace Wikibase;

/**
 * Removes a property info entry.
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
class PropertyInfoDeletion extends \DataUpdate {

	/**
	 * Constructor.
	 *
	 * @param EntityId          $id
	 * @param PropertyInfoStore $store
	 */
	public function __construct( EntityId $id, PropertyInfoStore $store ) {
		$this->propertyId = $id;
		$this->store = $store;
	}

	/**
	 * Perform the actual work
	 */
	function doUpdate() {
		wfDebugLog( __CLASS__, __FUNCTION__ . ': deleting property info for ' . $this->propertyId );
		$this->store->removePropertyInfo( $this->propertyId );
	}
}
