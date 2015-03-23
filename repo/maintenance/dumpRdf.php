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
		$dumpParts = array(
			'data' =>
				RdfProducer::PRODUCE_METADATA | RdfProducer::PRODUCE_ALL_STATEMENTS |
				RdfProducer::PRODUCE_TRUTHY_STATEMENTS |
				RdfProducer::PRODUCE_QUALIFIERS | RdfProducer::PRODUCE_REFERENCES |
				RdfProducer::PRODUCE_FULL_VALUES,
			'links' => RdfProducer::PRODUCE_SITELINKS,
			'labels' => RdfProducer::PRODUCE_EXTRA_LABELS,
		);
		// 'all' always means all
		$dumpParts['all'] = 0;
		foreach( $dumpParts as $part ) {
			$dumpParts['all'] |= $part;
		}

		$part = $this->getOption( 'part' );
		if( !isset( $dumpParts[$part] ) ) {
			throw new \MWException( "Unknown part type: $part" );
		}

	 	return RdfDumpGenerator::createDumpGenerator(
	 			$this->getOption( 'format', 'ttl' ),
	 			$output,
	 			$GLOBALS['wgCanonicalServer']."/entity/",
	 			$GLOBALS['wgCanonicalServer']."/Special:EntityData/",
	 			$this->wikibaseRepo->getSiteStore()->getSites(),
	 			$this->entityLookup, $this->revisionLookup,
	 			$this->wikibaseRepo->getPropertyDataTypeLookup(),
	 			$dumpParts[$part]
	 		);
	}
}

$maintClass = 'Wikibase\DumpRdf';
require_once( RUN_MAINTENANCE_IF_MAIN );
