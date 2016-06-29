<?php

namespace Wikibase\Lib;

use DataValues\DecimalValue;
use DataValues\QuantityValue;
use Wikibase\Rdf\RdfVocabulary;

/**
 * Convert quantities to other units.
 * @package Wikibase\Lib
 */
class UnitConverter {

	/**
	 * @var UnitStorage
	 */
	private $store;

	/**
	 * Prefix of the data entity (concept URI)
	 * @var string
	 */
	private $entityPrefix;

	public function __construct( UnitStorage $store, $entityPrefix ) {
		$this->store = $store;
		$this->prefix = $entityPrefix;
	}

	/**
	 * Convert QuantityValue to standard units
	 * @param QuantityValue $value
	 * @return QuantityValue Converted value in standard units, or original value if conversion
	 *     fails
	 */
	public function toStandardUnits( QuantityValue $value ) {
		$fromUnits = $value->getUnit();
		if ( substr( $fromUnits, 0, strlen( $this->prefix ) ) === $this->prefix ) {
			// Cut off prefix
			$fromUnits = substr( $fromUnits, strlen( $this->prefix ) );
		}
		$toUnits = $this->store->getConversion( $fromUnits );
		if ( !$toUnits ) {
			return $value;
		}

		$targetUint = $toUnits[1];
		if ( substr( $targetUint, 0, strlen( $this->prefix ) ) !== $this->prefix ) {
			$targetUint = $this->prefix . $targetUint;
		}
		$mult = $this->makeDecimalValue( $toUnits[0] );

		// TODO: make special case where amount == upper == lower
		return new QuantityValue(
			$this->convertValue( $value->getAmount(), $mult ),
			$targetUint,
			$this->convertValue( $value->getUpperBound(), $mult ),
			$this->convertValue( $value->getLowerBound(), $mult )
		);
	}

	private function makeDecimalValue($num) {
		if ( $num{0} == '-' ) {
			return new DecimalValue( $num );
		} else {
			return new DecimalValue( '+' . $num );
		}
	}

	/**
	 * Convert value by multiplying by given multiplier
	 *
	 * @param DecimalValue $fromValue
	 * @param DecimalValue $multiplier
	 * @return DecimalValue converted value
	 */
	protected function convertValue( DecimalValue $fromValue, DecimalValue $multiplier ) {
		$scale =
			strlen( $fromValue->getFractionalPart() ) + strlen( $multiplier->getFractionalPart() );
		return $this->makeDecimalValue( bcmul( $fromValue->getValue(), $multiplier->getValue(),
			$scale ) );
	}


}
