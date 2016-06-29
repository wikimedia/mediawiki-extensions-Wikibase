<?php
namespace Wikibase\Lib;

/** 
 * JSON based unit conversion storage.
 */
class JsonUnitStorage extends BaseUnitStorage {

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
		$data = file_get_contents( $this->sourceFile );
		if ( !$data ) {
			return null;
		}
		return json_decode( $data, true );
	}

}
