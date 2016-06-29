<?php
namespace Wikibase\Lib;

/**
 * Basic unit storage functionality.
 * Concrete classes need to fill in data loading.
 * @package Wikibase\Lib
 */
abstract class BaseUnitStorage implements UnitStorage {
	private $storageData;

	/**
	 * Load data from concrete storage
	 * @return array
	 */
	abstract protected function loadStorageData();

	protected function __construct() {
		$this->storageData = $this->loadStorageData();
		if ( !$this->storageData ) {
			throw new \RuntimeException( "Failed to load unit storage" );
		}
	}

	/**
	 * Check if certain unit is primary
	 * @param string $unit
	 * @return bool
	 */
	public function isPrimaryUnit( $unit ) {
		if ( !isset( $this->storageData[$unit] ) ) {
			return false;
		}
		return $this->storageData[$unit][1] === $unit;
	}

	/**
	 * Get conversion from this unit to primary unit
	 * @param string $unit
	 * @return array|null [$quantity, $targetUnit] or null if no conversion available or needed
	 */
	public function getConversion( $unit ) {
		if ( !isset( $this->storageData[$unit] ) ) {
			return null;
		}
		if ( $this->isPrimaryUnit( $unit ) ) {
			return null;
		}
		return $this->storageData[$unit];
	}
}
