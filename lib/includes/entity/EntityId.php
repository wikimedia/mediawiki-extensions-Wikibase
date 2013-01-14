<?php

namespace Wikibase;
use MWException;

/**
 * Represents an ID of an Entity.
 *
 * An Entity ID consists out of two parts.
 * - The entity type.
 * - A numerical value.
 *
 * The numerical value is sufficient to unequally identify
 * the Entity within a group of Entities of the same type.
 * It is not enough for unique identification in groups
 * of different Entity types, which is where the entity type
 * is also needed.
 *
 * To the outside world these IDs are only exposed in serialized
 * form where the entity type is turned into a prefix to which
 * the numerical part then gets concatenated.
 *
 * Internally the entity type should be used rather then the ID prefix.
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
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com
 * @author John Erling Blad < jeblad@gmail.com >
 */
class EntityId implements \Immutable, \Comparable {

	/**
	 * Constructs an EntityId object from a prefixed id.
	 *
	 * @since 0.3
	 * @deprecated since 0.4, use an EntityIdParser
	 *
	 * @param string $prefixedId
	 *
	 * @return EntityId|null
	 * @throws MWException
	 */
	public static function newFromPrefixedId( $prefixedId ) {
		$libRegistry = new LibRegistry( Settings::singleton() );
		$result = $libRegistry->getEntityIdParser()->parse( $prefixedId );
		return $result->isValid() ? $result->getValue() : null;
	}

	/**
	 * Returns the prefixed used when serializing the ID.
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getPrefix() {
		static $prefixMap = false;

		if ( $prefixMap === false ) {
			$prefixMap = array();

			// TODO: fix dependency on global state
			// Either the prefix or the needed option should be passe din the constructor.
			foreach ( Settings::get( 'entityPrefixes' ) as $prefix => $type ) {
				$prefixMap[$type] = $prefix;
			}
		}

		return $prefixMap[$this->entityType];
	}

	/**
	 * The type of the entity to which the ID belongs.
	 *
	 * @since 0.3
	 *
	 * @var string
	 */
	protected $entityType;

	/**
	 * The numeric ID of the entity.
	 *
	 * @since 0.3
	 *
	 * @var integer
	 */
	protected $numericId;

	/**
	 * Constructor.
	 *
	 * @since 0.3
	 *
	 * @param string $entityType
	 * @param integer $numericId
	 *
	 * @throws MWException
	 */
	public function __construct( $entityType, $numericId ) {
		if ( !is_string( $entityType ) ) {
			throw new MWException( '$entityType needs to be a string' );
		}

		if ( !is_integer( $numericId ) ) {
			throw new MWException( '$numericId needs to be an integer' );
		}

		$this->entityType = $entityType;
		$this->numericId = $numericId;
	}

	/**
	 * Returns the type of the entity.
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getEntityType() {
		return $this->entityType;
	}

	/**
	 * Returns the numeric id of the entity.
	 *
	 * @since 0.3
	 *
	 * @return integer
	 */
	public function getNumericId() {
		return $this->numericId;
	}

	/**
	 * Gets the serialized ID consisting out of entity type prefix followed by the numerical ID.
	 *
	 * @since 0.3
	 *
	 * @return string The prefixed id, or false if it can't be found
	 */
	public function getPrefixedId() {
		return $this->getPrefix() . $this->numericId;
	}

	/**
	 * @see Comparable::equals
	 *
	 * @since 0.3
	 *
	 * @param mixed $target
	 *
	 * @return boolean
	 */
	public function equals( $target ) {
		return $target instanceof EntityId
			&& $target->getNumericId() === $this->numericId
			&& $target->getEntityType() === $this->entityType;
	}

	/**
	 * Return a string representation of this entity id. Equal to getPrefixedId().
	 *
	 * @since 0.3
	 *
	 * @return String
	 */
	public function __toString() {
		return $this->getPrefixedId();
	}
}
