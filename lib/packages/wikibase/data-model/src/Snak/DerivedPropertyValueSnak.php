<?php

namespace Wikibase\DataModel\Snak;

use DataValues\DataValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * PropertyValueSnak with with derived values attached.
 * Derived DataValues can be used to provide information such as normalized values.
 *
 * Calls to the getType method will indicate that this is a PropertyValueSnak.
 *
 * Direct serialization of this object will not include the extra derived values.
 *
 * The hash of this object is not changed by additional Derived values.
 * The hash of this object will differ from the PropertyValueSnak of this object.
 *
 * This object will ->equal other DerivedPropertyValueSnaks with the same base PropertyValueSnak.
 * This object will NOT ->equal any PropertyValueSnaks.
 * A newPropertyValueSnak method is provided for comparison convenience.
 *
 * @since 3.1
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class DerivedPropertyValueSnak extends PropertyValueSnak {

	/**
	 * @var DataValue[]
	 */
	private $derivedDataValues = [];

	/**
	 * @param PropertyId|EntityId|int $propertyId
	 * @param DataValue $dataValue
	 * @param DataValue[] $derivedDataValues
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$propertyId,
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
		return $this->derivedDataValues[$key] ?? null;
	}

	/**
	 * @return PropertyValueSnak
	 */
	public function newPropertyValueSnak() {
		return new PropertyValueSnak( $this->propertyId, $this->dataValue );
	}

}
