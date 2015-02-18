<?php
namespace Wikibase;
use Wikibase\Dumpers\RdfDumpGenerator;

require_once __DIR__ . '/dumpEntities.php';

class DumpRdf extends DumpScript {

	public function __construct() {
		parent::__construct();
		$this->addOption( 'format', "Set the dump format.", false, true );
	}

	protected function createDumper( $output ) {
		$format = $dumpFormat = $this->getOption( 'format', 'ttl' );
		$rdfFormat = RdfSerializer::getFormat( $format );
		if( !$rdfFormat ) {
			throw new \MWException( "Unknown format: $format" );
		}
		$entitySerializer = new RdfSerializer( $rdfFormat,
				$GLOBALS['wgCanonicalServer']."/entity/",
				$GLOBALS['wgCanonicalServer']."/Special:EntityData/",
				$this->wikibaseRepo->getSiteStore()->getSites(),
				$this->entityLookup,
				RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_TRUTHY_STATEMENTS |
				RdfProducer::PRODUCE_QUALIFIERS | RdfProducer::PRODUCE_REFERENCES |
				RdfProducer::PRODUCE_SITELINKS
		);
		return new RdfDumpGenerator( $output, $this->revisionLookup, $entitySerializer );
	}
}

$maintClass = 'Wikibase\DumpRdf';
require_once( RUN_MAINTENANCE_IF_MAIN );
