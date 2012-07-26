<?php

namespace Wikibase; 

/**
 * @author Katie Filbert
 */

require_once( dirname( __FILE__ ) . '../../../../../maintenance/Maintenance.php' );

class PopulateInterwiki extends \Maintenance {

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
		$data = self::fetchLinks();
		self::doPopulate( $data, $force );
	}

	public function fetchLinks() {
		$url = 'http://en.wikipedia.org/w/api.php?action=query&meta=siteinfo&siprop=interwikimap&sifilteriw=local&format=json';

		$json = \Http::get( $url );
		$data = \FormatJSON::decode( $json, true );

		return $data['query']['interwikimap'];
	}

	public function doPopulate( $data, $force ) {
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

$maintClass = 'PopulateInterwiki';
require_once( RUN_MAINTENANCE_IF_MAIN );
