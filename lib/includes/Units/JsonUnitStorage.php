<?php
namespace Wikibase\Lib;

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
		parent::__construct();
	}

	/**
	 * Load data from concrete storage
	 * @return array
	 */
	protected function loadStorageData() {
		return json_decode( file_get_contents( $this->sourceFile ), true );
	}
	
}