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
		$fromUnit = $value->getUnit();

		if ( $fromUnit === '1' ) {
			// Won't convert unitless values
			return $value;
		}

		if ( substr( $fromUnit, 0, strlen( $this->prefix ) ) === $this->prefix ) {
			// Cut off prefix
			$fromUnit = substr( $fromUnit, strlen( $this->prefix ) );
		}
		$toUnits = $this->store->getConversion( $fromUnit );
		if ( !$toUnits ) {
			return $value;
		}

		$targetUnit = $toUnits[1];
		if ( $targetUnit === $fromUnit ) {
			// if target is the same as source, do not touch the value
			return $value;
		}
		if ( substr( $targetUnit, 0, strlen( $this->prefix ) ) !== $this->prefix ) {
			$targetUnit = $this->prefix . $targetUnit;
		}
		if ( $targetUnit === $fromUnit ) {
			// if target is the same as source, do not touch the value
			return $value;
		}
		$mult = $this->makeDecimalValue( $toUnits[0] );

		if ( $toUnits[0]{0} === '-' || $mult->isZero() ) {
			// We do not support negative multipliers, and zero multiplier makes no sense
			wfDebugLog( 'private', "Bad multiplier for $fromUnit: {$toUnits[0]}" );
			return $value;
		}

		// TODO: make special case where amount == upper == lower
		return new QuantityValue(
			$this->convertValue( $value->getAmount(), $mult ),
			$targetUnit,
			$this->convertValue( $value->getUpperBound(), $mult ),
			$this->convertValue( $value->getLowerBound(), $mult )
		);
	}

	/**
	 * Convert number string into decimal value.
	 * @param string $num
	 * @return DecimalValue
	 */
	protected function makeDecimalValue( $num ) {
		if ( strpos( $num, '.' ) !== - 1 ) {
			// cut trailing zeros
			$num = rtrim( $num, '0' );
			if ( substr( $num, -1 ) === '.' ) {
				// cut off trailing dot
				$num = substr( $num, 0, -1 );
			}
			if ( $num === '' ) {
				$num = '0';
			}
		}

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
