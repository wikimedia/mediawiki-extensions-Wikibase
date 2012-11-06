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
	 * Get individual parts of an id
	 *
	 * @since 0.2
	 *
	 * @param string $id
	 *
	 * @return the actual id broken up in prefix, number, hash and fragment and fragment alone
	 */
	protected static function getIdParts( $id ) {
		static $regex = false;

		if ( $regex === false ) {
			$prefixes = array_map(
				function( $prefix ) {
					if ( $prefix === '' ) {
						throw new MWException( 'Registered entity identifier prefix is an empty string.' );
					}
					return preg_quote( $prefix, '/' );
				},
				self::getEntityPrefixes()
			);
			$regex = '/^(' . implode( '|', $prefixes ) . '|)(\d+)(#.*|)$/';
		}

		preg_match( $regex, $id, $matches );
		return $matches;
	}

	/**
	 * Predicate to check if there is a prefix in place
	 * @since 0.2
	 *
	 * @param string $id
	 *
	 * @return bool true if a prefix is found, false if it is not found
	 */
	public function isPrefixedId( $id ) {
		$parts = self::getIdParts( $id );
		return ( isset( $parts[1] ) && $parts[1] !== '' );
	}

	/**
	 * Get the named entity type from an id
	 *
	 * @since 0.2
	 *
	 * @param string $id
	 *
	 * @return string|false the entity type, or false if supplied a typeless id
	 *         false is not returned because code is commented out
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

		$parts = self::getIdParts( strtolower( $id ) );

		// this crashes with present code, add later when we are not relying
		// on this code throwing exceptions
		//if ( !isset( $parts[1] ) || $parts[1] === '' ) {
		//	return false;
		//}

		if ( !isset( $parts[1] ) || !array_key_exists( $parts[1], $typeMap ) ) {
			throw new MWException( 'Unregistered entity identifier prefix "' . $parts[1] . '".' );
		}

		return $typeMap[$parts[1]];
	}

	/**
	 * Get the id with the type qualifier prepended
	 *
	 * This method assumes the $entityId is purely numeric
	 *
	 * @since 0.2
	 *
	 * @param string $entityType
	 * @param integer $entityId
	 *
	 * @return string|false the prefixed id, or false if it can't be found
	 */
	public function getPrefixedId( $entityType, $entityId ) {
		static $prefixMap = false;

		if ( $prefixMap === false ) {
			$prefixMap = array();

			foreach ( self::$prefixMap as $setting => $type ) {
				$prefixMap[$type] = Settings::get( $setting );
			}
		}

		return $prefixMap[$entityType] . $entityId;
	}

	/**
	 * Get the unprefixed version of the id
	 *
	 * This method will strip any existing qualifiers and fragments
	 *
	 * @since 0.2
	 *
	 * @param string $prefixedId
	 *
	 * @return integer|false the unprefixed id, or false if it can't be found
	 */
	public function getUnprefixedId( $prefixedId ) {
		$parts = self::getIdParts( $prefixedId );

		if ( !isset( $parts[2] ) || $parts[2] === '' ) {
			return false;
		}

		return (int)$parts[2];
	}

	/**
	 * Get the fragment from the id
	 *
	 * This method will not include the fragment separator
	 *
	 * @since 0.2
	 *
	 * @param string $id
	 *
	 * @return string|false the unprefixed id, or false if it can't be found
	 */
	public function getIdFragment( $id ) {
		$parts = self::getIdParts( $id );

		if ( !isset( $parts[3] ) ) {
			return false;
		}

		return ( $parts[3] === '' ? '' : substr( $parts[3], 1 ) );
	}

	/**
	 * Returns the prefixes for the identifiers of the entities.
	 *
	 * @since 0.2
	 *
	 * @return array all allowed prefixes
	 */
	public static function getEntityPrefixes() {
		static $prefixes = false;

		if ( $prefixes === false ) {
			$prefixes = array();

			foreach ( self::$prefixMap as $setting => $entityType ) {
				$prefixes[] = Settings::get( $setting );
			}

		}
		return $prefixes;
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
	 * @return boolean true if valid, false if not
	 */
	public function isEntityType( $type ) {
		return array_key_exists( $type, self::$typeMap );
	}

}
