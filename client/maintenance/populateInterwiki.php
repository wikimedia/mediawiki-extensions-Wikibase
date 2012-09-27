<?php

namespace Wikibase;
use Http, FormatJSON, Maintenance;

/**
 * Maintenance script that populates the interwiki table with list of sites 
 * as exists on Wikipedia, so interwiki links render properly.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

class PopulateInterwiki extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->mDescription = <<<TEXT
This script will populate the interwiki table, pulling in interwiki links 
that are used on Wikipedia.

When the script has finished, it will make a note of this in the database, and
will not run again without the --force option.
TEXT;

		$this->addOption( 'force', 'Run regardless of whether the database says it\'s been run already' );
	}

	public function execute() {
		$force = $this->getOption( 'force', false );
		$data = $this->fetchLinks();
		$this->doPopulate( $data, $force );
	}

	protected function fetchLinks() {
		$url = 'https://en.wikipedia.org/w/api.php?action=query&meta=siteinfo&siprop=interwikimap&sifilteriw=local&format=json';

		$json = Http::get( $url );
		$data = FormatJSON::decode( $json, true );

		return $data['query']['interwikimap'];
	}

	protected function doPopulate( $data, $force ) {
		$dbw = wfGetDB( DB_MASTER );

		if ( !$force ) {
			$row = $dbw->selectRow(
				'updatelog',
				'1',
				array( 'ul_key' => 'populate interwiki' ),
				__METHOD__
			);

			if ( $row ) {
				$this->output( "Interwiki table already populated.  Use php " .
					"maintenance/populateInterwiki.php\n--force from the command line " .
					"to override.\n" );
				return true;
			}
		}

		foreach( $data as $d ) {
			$row = $dbw->selectRow(
				'interwiki',
				'1',
				array( 'iw_prefix' => $d['prefix'] ),
				__METHOD__
			);
		
			if ( ! $row ) {
				$dbw->insert(
					'interwiki',
					array( 'iw_prefix' => $d['prefix'],
						'iw_url' => $d['url'],
						'iw_local' => 1
					),
					__METHOD__,
					'IGNORE'
				);
			}
		}
 		
		$this->output( "Interwiki links are populated.\n" );

		return true;
	}
}

$maintClass = 'Wikibase\PopulateInterwiki';
require_once( RUN_MAINTENANCE_IF_MAIN );
