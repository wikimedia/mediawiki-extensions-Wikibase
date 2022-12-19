<?php

namespace Wikibase\Lib\Units;

/**
 * Basic unit storage functionality.
 * Concrete classes need to fill in data loading.
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
abstract class BaseUnitStorage implements UnitStorage {

	/**
	 * @var array[]
	 */
	private $storageData;

	/**
	 * Load data from concrete storage.
	 * The method should return array indexed by source unit.
	 * Each row should be either [<factor>, <unit>] or
	 * ['factor' => <factor>, 'unit' => <unit>]
	 * @return array[]|null null when loading failed.
	 */
	abstract protected function loadStorageData();

	/**
	 * Load data from storage.
	 */
	private function loadData() {
		if ( $this->storageData === null ) {
			$this->storageData = $this->loadStorageData();
			if ( $this->storageData === null ) {
				throw new \RuntimeException( "Failed to load unit storage" );
			}
		}
	}

	/**
	 * Check if certain unit is primary.
	 * @param string $unit
	 * @return bool
	 */
	public function isPrimaryUnit( $unit ) {
		if ( $this->storageData === null ) {
			$this->loadData();
		}
		if ( !isset( $this->storageData[$unit] ) ) {
			return false;
		}
		return ( $this->storageData[$unit]['unit'] ?? $this->storageData[$unit][1] ) === $unit;
	}

	/**
	 * Get conversion from this unit to primary unit
	 * @param string $unit
	 * @return array|null 'factor' => factor from this unit to primary unit
	 *                    'unit' => primary unit
	 */
	public function getConversion( $unit ) {
		if ( $this->storageData === null ) {
			$this->loadData();
		}
		if ( !isset( $this->storageData[$unit] ) ) {
			return null;
		}
		if ( isset( $this->storageData[$unit]['factor'] ) ) {
			return $this->storageData[$unit];
		} else {
			return [
				'factor' => $this->storageData[$unit][0],
				'unit' => $this->storageData[$unit][1],
			];
		}
	}

}
