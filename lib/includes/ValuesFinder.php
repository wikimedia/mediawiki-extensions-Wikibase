<?php

namespace Wikibase;

use DataValues\DataValue;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\PropertyNotFoundException;
use Wikibase\DataModel\Snak\PropertyValueSnak;

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
			if ( $snak instanceof PropertyValueSnak &&
				$this->isMatchingDataType( $snak->getPropertyId(), $dataType )
			) {
				$dataValue = $snak->getDataValue();
				$found[$dataValue->getHash()] = $dataValue;
			}
		}

		return $found;
	}

	/**
	 * @param PropertyId $propertyId
	 * @param string $dataType
	 *
	 * @return bool
	 */
	private function isMatchingDataType( PropertyId $propertyId, $dataType ) {
		try {
			return $this->propertyDataTypeLookup->getDataTypeIdForProperty( $propertyId ) === $dataType;
		} catch ( PropertyNotFoundException $ex ) {
			return false;
		}
	}

}
