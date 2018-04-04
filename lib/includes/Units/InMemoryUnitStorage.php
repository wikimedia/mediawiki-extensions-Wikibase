<?php

namespace Wikibase\Lib\Units;

/**
 * Array-based static unit storage.
 * Mostly useful for tests.
 *
 * @license GPL-2.0-or-later
 * @author Lucas Werkmeister
 */
class InMemoryUnitStorage extends BaseUnitStorage {

	/**
	 * @var array[]|null
	 */
	private $data;

	/**
	 * @param array[]|null $data The data for the unit storage,
	 * a map from unit to an array of the form [ 'factor' => $factor, 'unit' => $unit ].
	 * 'null' simulates a failure to load data.
	 */
	public function __construct( array $data = null ) {
		$this->data = $data;
	}

	protected function loadStorageData() {
		return $this->data;
	}

}
