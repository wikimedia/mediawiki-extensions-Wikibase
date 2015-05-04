<?php

namespace Wikibase;

use Title;
use Wikibase\Dumpers\DumpGenerator;
use Wikibase\Dumpers\RdfDumpGenerator;
use Wikibase\Repo\Store\EntityPerPage;

require_once __DIR__ . '/dumpEntities.php';

class DumpRdf extends DumpScript {

	public function __construct() {
		parent::__construct();
		$this->addOption( 'format', "Set the dump format.", false, true );
	}

	/**
	 * Returns EntityPerPage::INCLUDE_REDIRECTS.
	 *
	 * @return mixed a EntityPerPage::XXX_REDIRECTS constant
	 */
	protected function getRedirectMode() {
		return EntityPerPage::INCLUDE_REDIRECTS;
	}

	/**
	 * Create concrete dumper instance
	 *
	 * @param resource $output
	 *
	 * @return DumpGenerator
	 */
	 protected function createDumper( $output ) {
		$entityDataTitle = Title::makeTitle( NS_SPECIAL, 'EntityData' );

		return RdfDumpGenerator::createDumpGenerator(
			$this->getOption( 'format', 'ttl' ),
			$output,
			$this->wikibaseRepo->getSettings()->getSetting( 'conceptBaseUri' ),
			$entityDataTitle->getCanonicalURL() . '/',
			$this->wikibaseRepo->getSiteStore()->getSites(),
			$this->revisionLookup,
			$this->wikibaseRepo->getPropertyDataTypeLookup(),
			$this->wikibaseRepo->getStore()->getEntityPrefetcher() );
	}
}

$maintClass = 'Wikibase\DumpRdf';
require_once RUN_MAINTENANCE_IF_MAIN;
