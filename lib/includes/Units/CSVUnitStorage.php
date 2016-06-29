<?php
namespace Wikibase\Lib;

/**
 * CSV-based unit conversion storage.
 * The units are stored as:
 *       Qsource,multiplier,QstandardUnit
 * E.g.: Q130964,4.19,Q25269
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
		for ( $row = fgetcsv( $f ); $row !== false; $row = fgetcsv( $f ) ) {
			$data[$row[0]] = array_slice( $row, 1 );
		}
		return $data;
	}

}
