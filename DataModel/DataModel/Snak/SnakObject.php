<?php

namespace Wikibase;

use DataValues\IllegalValueException;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Base class for snaks.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Snaks
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
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class SnakObject implements Snak {

	/**
	 * @since 0.1
	 *
	 * @var EntityId
	 */
	protected $propertyId;

	/**
	 * @since 0.1
	 *
	 * @param EntityId|integer $propertyId
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $propertyId ) {
		if ( is_integer( $propertyId ) ) {
			$propertyId = new EntityId( Property::ENTITY_TYPE, $propertyId );
		}

		if ( !$propertyId instanceof EntityId ) {
			throw new InvalidArgumentException( '$propertyId should be a EntityId' );
		}

		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new InvalidArgumentException( 'The $propertyId of a property snak can only be an ID of a Property object' );
		}

		$this->propertyId = $propertyId;
	}

	/**
	 * @see Snak::getPropertyId
	 *
	 * @since 0.1
	 *
	 * @return EntityId
	 */
	public function getPropertyId() {
		return $this->propertyId;
	}

	/**
	 * @see Snak::getHash
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash() {
		return sha1( serialize( $this ) );
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
		if ( is_object( $target ) && ( $target instanceof Snak ) ) {
			return $this->getHash() === $target->getHash();
		}

		return false;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( $this->propertyId->getNumericId() );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $serialized
	 *
	 * @return Snak
	 */
	public function unserialize( $serialized ) {
		$this->propertyId = new EntityId( Property::ENTITY_TYPE, (int)unserialize( $serialized ) );
	}

	/**
	 * @see Snak::toArray
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function toArray() {
		$data = array();

		$data[] = $this->getType();
		$data[] = $this->getPropertyId()->getNumericId();

		return $data;
	}

	/**
	 * Factory for constructing Snak objects from their array representation.
	 * This is their internal array representation, which should not be confused
	 * with whatever is used for external serialization.
	 *
	 * The array should have the following format:
	 * - snak type (string)
	 * - property id (int)
	 * [
	 * - data value (mixed)
	 * - data value type (string)
	 * ]
	 *
	 * @since 0.3
	 *
	 * @param array $data
	 *
	 * @return Snak
	 */
	public static function newFromArray( array $data ) {
		$snakType = array_shift( $data );

		$data[0] = new EntityId( Property::ENTITY_TYPE, $data[0] );

		if ( $snakType === 'value' ) {
			try {
				$data[1] = \DataValues\DataValueFactory::singleton()->newDataValue( $data[1], $data[2] );
				unset( $data[2] );
			} catch ( IllegalValueException $ex ) {
				// Substitute with a PropertyBadValueSnak

				//TODO: this behavior should be optional, but it's unclear how
				//      to pass the respective flag into this static method.
				//      Static context is bad, let's use a factory.

				$snakType = 'bad';

				$data = array(
					$data[0],
					$ex->getMessage(),
					$data[2],
					$data[1]
				);
			}
		}

		return self::newFromType( $snakType, $data );
	}

	/**
	 * Constructs a new snak of specified type and returns it.
	 *
	 * @since 0.3
	 *
	 * @param string $snakType
	 * @param array $constructorArguments
	 *
	 * @return Snak
	 * @throws InvalidArgumentException
	 */
	public static function newFromType( $snakType, array $constructorArguments ) {
		if ( $constructorArguments === array() || ( $snakType === 'value' ) && count( $constructorArguments ) < 2 ) {
			throw new InvalidArgumentException( __METHOD__ . ' got an array with to few constructor arguments' );
		}

		static $snakJar = array(
			'value' => '\Wikibase\PropertyValueSnak',
			'novalue' => '\Wikibase\PropertyNoValueSnak',
			'somevalue' => '\Wikibase\PropertySomeValueSnak',
			'bad' => '\Wikibase\PropertyBadValueSnak',
		);

		if ( !array_key_exists( $snakType, $snakJar ) ) {
			throw new InvalidArgumentException( 'Cannot construct a snak from array with unknown snak type "' . $snakType . '"' );
		}

		$snakClass = $snakJar[$snakType];

		$reflect  = new ReflectionClass( $snakClass ); //TODO: remember this in $snakJar
		$snak = $reflect->newInstanceArgs( $constructorArguments );

		return $snak;
	}

}
