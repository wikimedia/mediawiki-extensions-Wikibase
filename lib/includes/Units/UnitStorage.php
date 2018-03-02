<?php

namespace Wikibase\Lib\Units;

/**
 * Storage interface for Unit conversion information.
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
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
	 * @return array|null [ 'factor'=>$quantity, 'unit'=>$targetUnit ]
	 *                    or null if no conversion available or needed
	 */
	public function getConversion( $unit );

}
