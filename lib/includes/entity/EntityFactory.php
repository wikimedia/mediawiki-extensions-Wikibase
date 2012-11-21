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
 * @author John Erling Blad < jeblad@gmail.com >
 */
class EntityFactory {

	/**
	 * Maps entity types to objects representing the corresponding entity.
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
	 * @return array all available type identifiers
	 */
	public function getEntityTypes() {
		return array_keys( self::$typeMap );
	}

	/**
	 * Predicate if the provided string is a valid entity type identifier.
	 *
	 * @since 0.2
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	public function isEntityType( $type ) {
		return array_key_exists( $type, self::$typeMap );
	}

	/**
	 * Creates a new empty entity of the given type.
	 *
	 * @param String $type the entity type, use the XXX::ENTITY_TYPE constants.
	 *
	 * @return Entity the new, empty entity
	 * @throws \MWException if $type is not a valid entity type
	 */
	public function newEmpty( $type ) {
		if ( !$this->isEntityType( $type ) ) {
			throw new \MWException( "Not an entity type: $type" );
		}

		$class = self::$typeMap[ $type ];

		$obj = call_user_func( array( $class, 'newEmpty' ) );
		return $obj;
	}

}
