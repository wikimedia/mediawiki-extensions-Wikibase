<?php

namespace Wikibase\DataModel\Snak;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Base class for snaks.
 * See https://www.mediawiki.org/wiki/Wikibase/DataModel#Snaks
 *
 * @since 0.1
 *
 * @license GPL-2.0-or-later
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
	 * @param PropertyId|EntityId|int $propertyId
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $propertyId ) {
		if ( is_int( $propertyId ) ) {
			$propertyId = NumericPropertyId::newFromNumber( $propertyId );
		}

		if ( !( $propertyId instanceof EntityId ) ) {
			throw new InvalidArgumentException( '$propertyId must be an instance of EntityId' );
		}

		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new InvalidArgumentException( '$propertyId must have an entityType of ' . Property::ENTITY_TYPE );
		}

		if ( !( $propertyId instanceof PropertyId ) ) {
			$propertyId = new NumericPropertyId( $propertyId->getSerialization() );
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
	 * @return string
	 */
	public function getHash(): string {
		return sha1( $this->getSerializationForHash() );
	}

	/**
	 * The serialization to use for hashing, for compatibility reasons this is
	 * equivalent to the old (pre 7.4) PHP serialization.
	 *
	 * @return string
	 */
	public function getSerializationForHash(): string {
		$data = $this->serialize();
		return 'C:' . strlen( static::class ) . ':"' . static::class .
			'":' . strlen( $data ) . ':{' . $data . '}';
	}

	/**
	 *
	 * @since 0.3
	 *
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target ) {
		if ( $this === $target ) {
			return true;
		}

		return is_object( $target )
			&& get_called_class() === get_class( $target )
			&& $this->getHash() === $target->getHash();
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 7.0 serialization format changed in an incompatible way
	 *
	 * @return string
	 */
	public function serialize() {
		return $this->propertyId->getSerialization();
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $serialized
	 */
	public function unserialize( $serialized ) {
		try {
			$this->propertyId = new NumericPropertyId( $serialized );
		} catch ( InvalidArgumentException $ex ) {
			// Backwards compatibility with the previous serialization format
			$this->propertyId = NumericPropertyId::newFromNumber( unserialize( $serialized ) );
		}
	}

	public function __serialize(): array {
		return [ $this->propertyId->getSerialization() ];
	}

	public function __unserialize( array $serialized ): void {
		$this->propertyId = new NumericPropertyId( $serialized[0] );
	}

}
