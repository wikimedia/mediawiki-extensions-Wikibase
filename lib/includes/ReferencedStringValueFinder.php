<?php

namespace Wikibase;

use DataValues\StringValue;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\PropertyNotFoundException;

/**
 * Find all string values of a given data type in a list of snaks.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ReferencedStringValueFinder {

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @var string
	 */
	private $dataType;

	/**
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param string $dataType
	 */
	public function __construct( PropertyDataTypeLookup $propertyDataTypeLookup, $dataType ) {
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->dataType = $dataType;
	}

	/**
	 * Finds all references for the specified datatype
	 * in the given array of snaks.
	 *
	 * @param Snak[] $snaks
	 *
	 * @return string[]
	 */
	public function findFromSnaks( array $snaks ) {
		$found = array();

		foreach ( $snaks as $snak ) {
			if ( $this->isMatchingSnak( $snak ) ) {
				$found[] = $snak->getDataValue()->getValue();
			}
		}

		return array_unique( $found );
	}

	private function isMatchingSnak( Snak $snak ) {
		if ( !$snak instanceof PropertyValueSnak ) {
			return false;
		}

		try {
			$type = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $snak->getPropertyId() );
		} catch ( PropertyNotFoundException $ex ) {
			return false;
		}

		if ( $type !== $this->dataType ) {
			return false;
		}

		$dataValue = $snak->getDataValue();

		if ( !$dataValue instanceof StringValue ) {
			return false;
		}

		return true;
	}

}
