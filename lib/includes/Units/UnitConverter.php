<?php

namespace Wikibase\Lib;

use DataValues\DecimalMath;
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
		$this->math = new DecimalMath();
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

		$targetUnit = $toUnits['unit'];
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
		$mult = $this->makeDecimalValue( $toUnits['factor'] );

		if ( $toUnits['factor']{0} === '-' || $mult->isZero() ) {
			// We do not support negative conversion factors, and zero factor makes no sense
			wfDebugLog( 'private', "Bad factor for $fromUnit: {$toUnits['factor']}" );
			return $value;
		}

		return $value->transform( $targetUnit, [ $this->math, 'product' ], $mult );
	}

	/**
	 * Create DecimalValue from regular numeric string or value.
	 * @param int|float|string $number
	 * FIXME: replace with DecimalValue method from https://github.com/DataValues/Number/pull/67
	 * @return DecimalValue
	 */
	private function makeDecimalValue( $number ) {

		if ( is_string( $number ) && $number !== '' ) {
			if ( $number[0] !== '-' && $number[0] !== '+' ) {
				$number = '+' . $number;
			}
		}

		return new DecimalValue( $number );
	}

}
