<?php
namespace Wikibase;
use Wikibase\Dumpers\JsonDumpGenerator;

require_once __DIR__ . '/dumpEntities.php';

class DumpJson extends DumpScript {

	public function __construct() {
		parent::__construct();
		$this->addOption( 'snippet', "Output a JSON snippet without square brackets at the start and end. Allows output to be combined more freely.", false, false );
	}

	private function createDumper( $output ) {
		$dumper = new JsonDumpGenerator( $output, $this->entityLookup, $this->entitySerializer );
		$dumper->setUseSnippets( (bool)$this->getOption( 'snippet', false ) );
		return $dumper;
	}
}

$maintClass = 'Wikibase\DumpJson';
require_once( RUN_MAINTENANCE_IF_MAIN );
