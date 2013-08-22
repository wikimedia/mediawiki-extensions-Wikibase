<?php

namespace Wikibase;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Base class for snaks.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Snaks
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
	 * @var PropertyId
	 */
	protected $propertyId;

	/**
	 * Support for passing in an EntityId instance that is not a PropertyId instance has
	 * been deprecated since 0.5.
	 *
	 * @since 0.1
	 *
	 * @param PropertyId|EntityId|integer $propertyId
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $propertyId ) {
		if ( is_integer( $propertyId ) ) {
			$propertyId = PropertyId::newFromNumber( $propertyId );
		}

		if ( !$propertyId instanceof EntityId ) {
			throw new InvalidArgumentException( '$propertyId should be a PropertyId' );
		}

		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new InvalidArgumentException( 'The $propertyId of a property snak can only be an ID of a Property object' );
		}

		if ( !( $propertyId instanceof PropertyId ) ) {
			$propertyId = new PropertyId( $propertyId->getSerialization() );
		}

		$this->propertyId = $propertyId;
	}

	/**
	 * @see Snak::getPropertyId
	 *
	 * @since 0.1
	 *
	 * @return PropertyId
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
		$this->propertyId = PropertyId::newFromNumber( unserialize( $serialized ) );
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
	 * @note: If a DataValue could not be unserialized properly, the respective data
	 *        will be represented using an UnDeserializableValue object.
	 *
	 * @since 0.3
	 * @deprecated since 0.4
	 *
	 * @param array $data
	 *
	 * @return Snak
	 */
	public static function newFromArray( array $data ) {
		$snakType = array_shift( $data );

		$data[0] = PropertyId::newFromNumber( $data[0] );

		if ( $snakType === 'value' ) {
			$data[1] = \DataValues\DataValueFactory::singleton()->tryNewDataValue( $data[1], $data[2] );
			unset( $data[2] );
		}

		return self::newFromType( $snakType, $data );
	}

	/**
	 * Constructs a new snak of specified type and returns it.
	 *
	 * @since 0.3
	 * @deprecated since 0.4
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

		$snakJar = array(
			'value' => '\Wikibase\PropertyValueSnak',
			'novalue' => '\Wikibase\PropertyNoValueSnak',
			'somevalue' => '\Wikibase\PropertySomeValueSnak',
		);

		if ( !array_key_exists( $snakType, $snakJar ) ) {
			throw new InvalidArgumentException( 'Cannot construct a snak from array with unknown snak type "' . $snakType . '"' );
		}

		$snakClass = $snakJar[$snakType];

		if ( $snakType === 'value' ) {
			return new $snakClass(
				$constructorArguments[0],
				$constructorArguments[1]
			);
		}
		else {
			return new $snakClass( $constructorArguments[0] );
		}
	}

}
