<?php

namespace Wikibase;

use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\PropertyNotFoundException;

/**
 * Find all instances of a given data value type
 * for a specified data type in an array of snaks.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ValuesFinder {

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	public function __construct( PropertyDataTypeLookup $propertyDataTypeLookup ) {
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
	}

	/**
	 * Find all instances of the given data value type
	 * for the specified data type in the array of snaks.
	 *
	 * @param Snak[] $snaks
	 * @param string $dataType
	 * @param string $dataValueType
	 *
	 * @return DataValue[]
	 */
	public function findFromSnaks( array $snaks, $dataType, $dataValueType ) {
		$found = array();

		foreach ( $snaks as $snak ) {
			if ( $this->isMatchingSnak( $snak, $dataType, $dataValueType ) ) {
				// @todo is the hash for uniqueness really needed?
				$dataValue = $snak->getDataValue();
				$found[$dataValue->getHash()] = $dataValue;
			}
		}

		return $found;
	}

	private function isMatchingSnak( Snak $snak, $dataType, $dataValueType ) {
		if ( !$snak instanceof PropertyValueSnak ) {
			return false;
		}

		try {
			$type = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $snak->getPropertyId() );
		} catch ( PropertyNotFoundException $ex ) {
			return false;
		}

		if ( $type !== $dataType ) {
			return false;
		}

		$dataValue = $snak->getDataValue();

		if ( !( $dataValue instanceof $dataValueType ) ) {
			return false;
		}

		return true;
	}

}
