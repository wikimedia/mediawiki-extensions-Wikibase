<?php

namespace Wikibase;
use MWException;

/**
 * Factory for Entity objects.
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
 * @since 0.2
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityFactory {

	/**
	 * Maps entity types to objects representing the corresponding entity.
	 * TODO: put this on a better place.
	 *
	 * @since 0.2
	 *
	 * @var array
	 */
	protected static $typeMap = array(
		Item::ENTITY_TYPE => '\Wikibase\ItemObject',
		Property::ENTITY_TYPE => '\Wikibase\PropertyObject',
		Query::ENTITY_TYPE => '\Wikibase\QueryObject'
	);

	/**
	 * @since 0.2
	 *
	 * @return EntityFactory
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new static();
		}

		return $instance;
	}

	/**
	 * Returns the type identifiers of the entities.
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function getEntityTypes() {
		return array_keys( self::$typeMap );
	}

	/**
	 * Returns if the provided string is a valid entity type identifier.
	 *
	 * @since 0.2
	 *
	 * @param string $type
	 *
	 * @return boolean
	 */
	public function isEntityType( $type ) {
		return in_array( $type, self::$typeMap );
	}

}
