<?php

namespace Wikibase\DataModel\Snak;

use DataValues\DataValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Class representing a property value snak.
 * See https://www.mediawiki.org/wiki/Wikibase/DataModel#PropertyValueSnak
 *
 * @since 0.1
 *
 * @license GPL-2.0+
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
	 * @see Serializable::serialize
	 *
	 * @since 7.0 serialization format changed in an incompatible way
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( [ $this->propertyId->getSerialization(), $this->dataValue ] );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $serialized
	 */
	public function unserialize( $serialized ) {
		list( $propertyId, $this->dataValue ) = unserialize( $serialized );

		if ( is_string( $propertyId ) ) {
			$this->propertyId = new PropertyId( $propertyId );
		} else {
			// Backwards compatibility with the previous serialization format
			$this->propertyId = PropertyId::newFromNumber( $propertyId );
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
