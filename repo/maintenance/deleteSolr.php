<?php

/**
 * Maintenance script for deleting the content of the Solr store
 *
 * php deleteSolr.php --verbose --ignore-errors
 *
 * @since 0.2
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Denny Vrandecic <vrandecic@gmail.com>
 */

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';
require_once $basePath . '/includes/Exception.php';

class deleteSolr extends Maintenance {
	protected $verbose = false;
	protected $ignore_errors = false;

	public function __construct() {
		$this->mDescription = "Delete the Solr search content.\n\nSolr is an optional extension to Wikidata to help with search.";

		$this->addOption( 'verbose', "Print activity " );
		$this->addOption( 'ignore-errors', "Continue after errors" );

		parent::__construct();
	}

	public function execute() {
		$this->verbose = (bool)$this->getOption( 'verbose' );
		$this->ignore_errors = (bool)$this->getOption( 'ignore-errors' );

		global $wgWBSolarium;
		require_once $wgWBSolarium;
		Solarium_Autoloader::register();

		$client = new Solarium_Client();
		$update = $client->createUpdate();
		$update->addDeleteQuery('*:*');
		$update->addCommit();

		$result = $client->update($update);

		$this->maybePrint( 'Query status: ' . $result->getStatus() );
		$this->maybePrint( 'Query time: ' . $result->getQueryTime() );
		$this->maybePrint( "Done." );
	}

	/**
	 * Print a scalar, array or object if --verbose option is set.
	 *
	 * @see importInterlang::doPrint()
	 * @see Maintenance::output()
	 */
	protected function maybePrint( $a ) {
		if( $this->verbose ) {
			$this->doPrint( $a );
		}
	}

	/**
	 * Output a scalar, array or object to the default channel
	 *
	 * @see Maintenance::output()
	 */
	protected function doPrint( $a ) {
		if( is_null( $a ) ) {
			$a = 'null';
		} elseif( is_bool( $a ) ) {
			$a = ( $a? "true\n": "false\n" );
		} elseif( !is_scalar( $a ) ) {
			$a = print_r( $a, true );
		}

		$this->output( trim( strval( $a ) ) . "\n" );
	}

}

$maintClass = 'deleteSolr';
require_once( RUN_MAINTENANCE_IF_MAIN );
