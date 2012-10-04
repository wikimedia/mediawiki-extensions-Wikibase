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

	// TODO: move to sane place
	protected static $prefixMap = array(
		'itemPrefix' => Item::ENTITY_TYPE,
		'propertyPrefix' => Property::ENTITY_TYPE,
		'queryPrefix' => Query::ENTITY_TYPE,
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
	 * @since 0.2
	 *
	 * @param string $id
	 *
	 * @return string
	 * @throws \MWException
	 */
	public function getEntityTypeFromPrefixedId( $id ) {
		static $typeMap = false;

		if ( $typeMap === false ) {
			$typeMap = array();

			foreach ( self::$prefixMap as $setting => $entityType ) {
				$typeMap[Settings::get( $setting )] = $entityType;
			}
		}

		// TODO: get rid of assumption length=1
		$prefix = substr( $id, 0, 1 );

		if ( !array_key_exists( $prefix, $typeMap ) ) {
			throw new MWException( 'Unregistered entity identifier prefix "' . $prefix . '".' );
		}

		return $typeMap[$prefix];
	}

	/**
	 * @since 0.2
	 *
	 * @param string $entityType
	 * @param integer $entityId
	 *
	 * @return string
	 */
	public function getPrefixedId( $entityType, $entityId ) {
		static $prefixMap = false;

		if ( $prefixMap === false ) {
			$prefixMap = array();

			foreach ( self::$prefixMap as $setting => $entityType ) {
				$prefixMap[$entityType] = Settings::get( $setting );
			}
		}

		return $prefixMap[$entityType] . $entityId;
	}

	/**
	 * @since 0.2
	 *
	 * @param string$prefixedId
	 *
	 * @return integer
	 */
	public function getUnprefixedId( $prefixedId ) {
		// TODO: get rid of assumption length=1
		return (int)substr( $prefixedId, 1 );
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
		return array_key_exists( $type, self::$typeMap );
	}

}
