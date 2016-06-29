<?php
namespace Wikibase\Lib;

/**
 * Basic unit storage functionality.
 * Concrete classes need to fill in data loading.
 * @package Wikibase\Lib
 */
abstract class BaseUnitStorage implements UnitStorage {
	/**
	 * Storage data.
	 * @var
	 */
	private $storageData;

	/**
	 * Load data from concrete storage.
	 * The method should return array indexed by source unit.
	 * Each row should be either [<multiplier>, <unit>] or
	 * ['multiplier' => <multiplier>, 'unit' => <unit>]
	 * @return array|null null when loading failed.
	 */
	abstract protected function loadStorageData();

	/**
	 * Load data from storage.
	 */
	protected function loadData() {
		if ( is_null( $this->storageData ) ) {
			$this->storageData = $this->loadStorageData();
			if ( !$this->storageData ) {
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
		if ( is_null( $this->storageData ) ) {
			$this->loadData();
		}
		if ( !isset( $this->storageData[$unit] ) ) {
			return false;
		}
		if ( isset( $this->storageData[$unit]['unit'] ) ) {
			return $this->storageData[$unit]['unit'] === $unit;
		} else {
			return $this->storageData[$unit][1] === $unit;
		}
	}

	/**
	 * Get conversion from this unit to primary unit
	 * @param string $unit
	 * @return array|null 'multiplier' => multiplier from this unit to primary unit
	 *                    'unit' => primary unit
	 */
	public function getConversion( $unit ) {
		if ( is_null( $this->storageData ) ) {
			$this->loadData();
		}
		if ( !isset( $this->storageData[$unit] ) ) {
			return null;
		}
		if ( $this->isPrimaryUnit( $unit ) ) {
			return null;
		}
		if ( isset( $this->storageData[$unit]['multiplier'] ) ) {
			return $this->storageData[$unit];
		} else {
			return [
				'multiplier' => $this->storageData[$unit][0],
				'unit' => $this->storageData[$unit][1]
			];
		}
	}

}
