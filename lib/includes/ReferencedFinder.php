<?php

namespace Wikibase;

use DataValues\DataValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\PropertyNotFoundException;

/**
 * Abstract base class for referenced finder classes.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class ReferencedFinder {

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @var string[]
	 */
	protected $found;

	public function __construct( PropertyDataTypeLookup $propertyDataTypeLookup ) {
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
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
		$this->found = array();

		foreach ( $snaks as $snak ) {
			$this->handleSnak( $snak );
		}

		return array_unique( $this->found );
	}

	private function handleSnak( Snak $snak ) {
		if( $snak instanceof PropertyValueSnak ) {
			if ( $this->isMatchingProperty( $snak->getPropertyId() ) ) {
				$this->handleDataValue( $snak->getDataValue() );
			}
		}
	}

	private function isMatchingProperty( PropertyId $propertyId ) {
		try {
			$type = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $propertyId );
		} catch ( PropertyNotFoundException $ex ) {
			return false;
		}

		$expectedType = $this->getDataType();

		return $type === $expectedType;
	}

	private function handleDataValue( DataValue $dataValue ) {
		$value = $this->getValueForDataValue( $dataValue );
		if ( $value !== null ) {
			$this->found[] = $value;
		}
	}

	/**
	 * Returns the data type this finder wants to look for.
	 *
	 * @return string
	 */
	protected abstract function getDataType();

	/**
	 * Returns the value for the given data value object
	 * or null if the data value is invalid.
	 *
	 * @param DataValue $dataValue
	 *
	 * @return string|null
	 */
	protected abstract function getValueForDataValue( DataValue $dataValue );

}
