<?php

namespace Wikibase;

/**
 * Finds linked entities given a list of entities or a list of claims.
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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author Daniel Kinzler
 */
class ReferencedEntitiesFinder {

	/**
	 * @param Snak[] $snaks
	 *
	 * @return EntityId[]
	 */
	public function findSnakLinks( array $snaks ) {
		$foundEntities = array();

		foreach ( $snaks as $snak ) {
			// all of the Snak's properties are referenced entities, add them:
			$foundEntities[] = $snak->getPropertyId();

			// PropertyValueSnaks might have a value referencing an Entity, find those as well:
			if( $snak instanceof PropertyValueSnak ) {
				$snakValue = $snak->getDataValue();

				if( $snakValue === null ) {
					// shouldn't ever run into this, but make sure!
					continue;
				}

				switch( $snakValue->getType() ) {
					case 'wikibase-entityid':
						if( $snakValue instanceof EntityId ) {
							$foundEntities[] = $snakValue;
						}
						break;
					// TODO: handle values in other formats. E.g. in an earlier version the
					//  'wikibase-entity' data type has been using 'string' values to store its ID.

					// TODO: we might want to allow extensions to add handling for their custom
					//  data value types here. Either use a hook or a proper registration for that.
				}
			}
		}

		return array_unique( $foundEntities );
	}

}


