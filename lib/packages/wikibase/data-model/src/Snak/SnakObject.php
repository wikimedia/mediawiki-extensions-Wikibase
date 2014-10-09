<?php

namespace Wikibase\DataModel\Snak;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Base class for snaks.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Snaks
 *
 * @since 0.1
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
	 * @see PropertyIdProvider::getPropertyId
	 *
	 * @since 0.1
	 *
	 * @return PropertyId
	 */
	public function getPropertyId() {
		return $this->propertyId;
	}

	/**
	 * @see Hashable::getHash
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
	 * @return bool
	 */
	public function equals( $target ) {
		if ( $target === $this ) {
			return true;
		}

		if ( !( $target instanceof Snak ) ) {
			return false;
		}

		return $this->getHash() === $target->getHash();
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
	 */
	public function unserialize( $serialized ) {
		$this->propertyId = PropertyId::newFromNumber( unserialize( $serialized ) );
	}

}
