<?php
namespace Wikibase\Lib;

/**
 * Storage interface for Unit conversion information.
 * @package Wikibase\Lib
 */
interface UnitStorage {

	/**
	 * Check if the unit is primary
	 * @param string $unit
	 * @return bool
	 */
	public function isPrimaryUnit( $unit );

	/**
	 * Get conversion from this unit to primary unit
	 * @param string $unit
	 * @return array|null [$quantity, $targetUnit] or null if no conversion available or needed
	 */
	public function getConversion( $unit );
}
