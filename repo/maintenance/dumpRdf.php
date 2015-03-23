<?php
namespace Wikibase;
use Wikibase\Dumpers\RdfDumpGenerator;

require_once __DIR__ . '/dumpEntities.php';

class DumpRdf extends DumpScript {

	public function __construct() {
		parent::__construct();
		$this->addOption( 'format', "Set the dump format.", false, true );
		$this->addOption( 'part', "Set the dump part, one of: statements, simple, links, labels, all.", true, true );
	}

	/**
	 * Create concrete dumper instance
	 * @param resource $output
	 * @return DumpGenerator
	 */
	 protected function createDumper( $output ) {

	 	return RdfDumpGenerator::createDumpGenerator(
	 			$this->getOption( 'format', 'ttl' ),
	 			$output,
	 			$GLOBALS['wgCanonicalServer']."/entity/",
	 			$GLOBALS['wgCanonicalServer']."/Special:EntityData/",
	 			$this->wikibaseRepo->getSiteStore()->getSites(),
	 			$this->entityLookup, $this->revisionLookup,
	 			$this->wikibaseRepo->getPropertyDataTypeLookup(),
	 			RdfDumpGenerator::getDumpPartFlavor( $this->getOption( 'part' ) )
	 		);
	}
}

$maintClass = 'Wikibase\DumpRdf';
require_once( RUN_MAINTENANCE_IF_MAIN );
