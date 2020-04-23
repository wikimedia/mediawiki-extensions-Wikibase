<?php

namespace Wikibase\Lib\Units;

/**
 * JSON based unit conversion storage.
 * Storage format:
 * Json map, each element is 'Qsource': [ <factor>, 'QstandardUnit' ]
 * Example:
 * {
 * "Q103510": [ "100000", "Q44395" ],
 * "Q130964": [ "4.19", "Q25269" ],
 * "Q182429": [ "1", "Q182429" ]
 * }
 * Q182429 here is a primary unit since source and standard are the same.
 * Primary units must have factor of 1.
 * Another acceptable format is:
 * "Q103510": { "factor": "100000", "unit": "Q44395" },
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class JsonUnitStorage extends BaseUnitStorage {

	/**
	 * Filename of the source file
	 * @var string
	 */
	private $sourceFile;

	/**
	 * @param string $fileName Filename of the storage file.
	 */
	public function __construct( $fileName ) {
		$this->sourceFile = $fileName;
	}

	/**
	 * Load data from concrete storage
	 * @return array[]|null
	 */
	protected function loadStorageData() {
		if ( !is_readable( $this->sourceFile ) ) {
			return null;
		}
		$data = file_get_contents( $this->sourceFile );
		if ( !$data ) {
			return null;
		}
		return json_decode( $data, true );
	}

}
