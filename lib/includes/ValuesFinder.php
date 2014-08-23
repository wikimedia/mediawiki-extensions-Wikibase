<?php

namespace Wikibase;

use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\PropertyNotFoundException;

/**
 * Find all data values for a specified data type in an array of snaks.
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
	 * Find all data values for the specified data type in the array of snaks.
	 *
	 * @param Snak[] $snaks
	 * @param string $dataType
	 *
	 * @return DataValue[]
	 */
	public function findFromSnaks( array $snaks, $dataType ) {
		$found = array();

		foreach ( $snaks as $snak ) {
			if ( $this->isMatchingSnak( $snak, $dataType ) ) {
				$dataValue = $snak->getDataValue();
				$found[$dataValue->getHash()] = $dataValue;
			}
		}

		return $found;
	}

	private function isMatchingSnak( Snak $snak, $dataType ) {
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

		return true;
	}

}
