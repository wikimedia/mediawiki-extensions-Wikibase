<?php

namespace Wikibase\DataModel\Services\DataValue;

use DataValues\DataValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * Find all data values for a specified data type in an array of snaks.
 *
 * @since 1.1
 *
 * @license GPL-2.0-or-later
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
		$found = [];

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
		} catch ( PropertyDataTypeLookupException $ex ) {
			return false;
		}
	}

}
