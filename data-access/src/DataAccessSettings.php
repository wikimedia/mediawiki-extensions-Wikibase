<?php

namespace Wikibase\DataAccess;

/**
 * Provides access to settings relevant for services in the data access component.
 *
 * @license GPL-2.0-or-later
 */
class DataAccessSettings {

	/**
	 * @var int
	 */
	private $maxSerializedEntitySizeInBytes;

	public function __construct(
		int $maxSerializedEntitySizeInKiloBytes
	) {
		$this->maxSerializedEntitySizeInBytes = $maxSerializedEntitySizeInKiloBytes * 1024;
	}

	/**
	 * @return int
	 */
	public function maxSerializedEntitySizeInBytes() {
		return $this->maxSerializedEntitySizeInBytes;
	}

}
