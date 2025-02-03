<?php

namespace Wikibase\DataModel\Snak;

use DataValues\DataValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Class representing a property value snak.
 * See https://www.mediawiki.org/wiki/Wikibase/DataModel#PropertyValueSnak
 *
 * @since 0.1
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class PropertyValueSnak extends SnakObject {

	protected $dataValue;

	/**
	 * Support for passing in an EntityId instance that is not a PropertyId instance has
	 * been deprecated since 0.5.
	 *
	 * @since 0.1
	 *
	 * @param PropertyId|EntityId|int $propertyId
	 * @param DataValue $dataValue
	 */
	public function __construct( $propertyId, DataValue $dataValue ) {
		parent::__construct( $propertyId );
		$this->dataValue = $dataValue;
	}

	/**
	 * Returns the value of the property value snak.
	 *
	 * @since 0.1
	 *
	 * @return DataValue
	 */
	public function getDataValue() {
		return $this->dataValue;
	}

	/**
	 * The serialization to use for hashing, for compatibility reasons this is
	 * equivalent to the old (pre 7.4) PHP serialization.
	 *
	 * @return string
	 */
	public function getSerializationForHash(): string {
		$propertyIdSerialization = $this->propertyId->getSerialization();
		$innerSerialization = 'a:2:{i:0;s:' . strlen( $propertyIdSerialization ) . ':"' .
			$propertyIdSerialization . '";i:1;' . $this->getDataValueSerializationForHash() . '}';

		return 'C:' . strlen( static::class ) . ':"' . static::class .
			'":' . strlen( $innerSerialization ) . ':{' . $innerSerialization . '}';
	}

	/**
	 * The serialization to use for hashing, for compatibility reasons this is
	 * equivalent to the old (pre 7.4) PHP serialization.
	 *
	 * @return string
	 */
	private function getDataValueSerializationForHash(): string {
		if ( method_exists( $this->dataValue, 'getSerializationForHash' ) ) {
			// If our DataValue provides/ needs a special serialization for
			// hashing, use it (currently only EntityIdValue).
			return $this->dataValue->getSerializationForHash();
		} else {
			$innerSerialization = $this->dataValue->serialize();
		}
		$className = get_class( $this->dataValue );

		return 'C:' . strlen( $className ) . ':"' . $className .
			'":' . strlen( $innerSerialization ) . ':{' . $innerSerialization . '}';
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 7.0 serialization format changed in an incompatible way
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( $this->__serialize() );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $serialized
	 */
	public function unserialize( $serialized ) {
		$this->__unserialize( unserialize( $serialized ) );
	}

	public function __serialize(): array {
		return [ $this->propertyId->getSerialization(), $this->dataValue ];
	}

	public function __unserialize( array $serialized ): void {
		list( $propertyId, $this->dataValue ) = $serialized;

		if ( is_string( $propertyId ) ) {
			$this->propertyId = new NumericPropertyId( $propertyId );
		} else {
			// Backwards compatibility with the previous serialization format
			$this->propertyId = NumericPropertyId::newFromNumber( $propertyId );
		}
	}

	/**
	 * @see Snak::getType
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getType() {
		return 'value';
	}

}
