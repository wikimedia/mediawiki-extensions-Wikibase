<?php

namespace Wikibase;

use DataValues\UnDeserializableValue;
use DataValues\DataValue;

/**
 * Class representing a property value snak.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#PropertyValueSnak
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class PropertyValueSnak extends SnakObject {

	/**
	 * @since 0.1
	 *
	 * @var DataValue
	 */
	protected $dataValue;

	/**
	 * @since 0.1
	 *
	 * @param EntityId|integer $propertyId
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
	 * @since 0.1
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( array( $this->propertyId->getNumericId(), $this->dataValue ) );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $serialized
	 *
	 * @return PropertyValueSnak
	 */
	public function unserialize( $serialized ) {
		list( $propertyId, $dataValue ) = unserialize( $serialized );

		$this->__construct(
			new EntityId( Property::ENTITY_TYPE, $propertyId ),
			$dataValue
		);
	}

	/**
	 * @see Snak::toArray
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function toArray() {
		$data = parent::toArray();

		// Since we use getArrayValue() and getType() directly instead of
		// the generic toArray() method, we need to handle the special case
		// of "bad" values separately, to restore the original type info.
		if ( $this->dataValue instanceof UnDeserializableValue ) {
			$type = $this->dataValue->getTargetType();
		} else {
			$type = $this->dataValue->getType();
		}

		$data[] = $type;
		$data[] = $this->dataValue->getArrayValue();

		return $data;
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