<?php

namespace Wikibase\DataModel\Snak;

use DataValues\DataValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * PropertyValueSnak with with derived values attached.
 * Derived DataValues can be used to provide infomation, for example, normalized values.
 *
 * Calls to the getType method will indicate that this is a PropertyValueSnak
 *
 * Direct serialization of this object will not include the extra derived values
 *
 * The hash of this object is not changed by the extra Derived values
 * thus this object will ->equal a PropertyValueSnak of the same value.
 *
 * @since 3.1
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class DerivedPropertyValueSnak extends PropertyValueSnak {

	/**
	 * @var DataValue[]
	 */
	private $derivedDataValues = array();

	/**
	 * @param PropertyId $propertyId
	 * @param DataValue $dataValue
	 * @param DataValue[] $derivedDataValues
	 */
	public function __construct(
		PropertyId $propertyId,
		DataValue $dataValue,
		array $derivedDataValues
	) {
		parent::__construct( $propertyId, $dataValue );

		foreach ( $derivedDataValues as $key => $extensionDataValue ) {
			if ( !( $extensionDataValue instanceof DataValue ) || !is_string( $key ) ) {
				throw new InvalidArgumentException(
					'$derivedDataValues must be an array of DataValue objects with string keys'
				);
			}
		}

		$this->derivedDataValues = $derivedDataValues;
	}

	/**
	 * @return DataValue[] with string keys
	 */
	public function getDerivedDataValues() {
		return $this->derivedDataValues;
	}

	/**
	 * @param string $key
	 *
	 * @return DataValue|null
	 */
	public function getDerivedDataValue( $key ) {
		if ( isset( $this->derivedDataValues[$key] ) ) {
			return $this->derivedDataValues[$key];
		}
		return null;
	}

}
