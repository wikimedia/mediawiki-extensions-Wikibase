<?php
namespace Wikibase\Lib;

/**
 * CSV-based unit conversion storage.
 */
class CSVUnitStorage extends BaseUnitStorage {
	/**
	 * Filename of the source file
	 * @var string
	 */
	private $sourceFile;

	/**
	 * JsonUnitStorage constructor.
	 * @param string $fileName Filename of the storage file.
	 */
	public function __construct( $fileName ) {
		$this->sourceFile = $fileName;
	}

	/**
	 * Load data from concrete storage
	 * @return array|null
	 */
	protected function loadStorageData() {
		if ( !is_readable( $this->sourceFile ) ) {
			return null;
		}
		$f = fopen( $this->sourceFile, 'r' );
		if ( !$f ) {
			return null;
		}
		$data = [];
		while(($row = fgetcsv($f)) !== false) {
			$data[] = $row;
		}
		return $data;
	}

}