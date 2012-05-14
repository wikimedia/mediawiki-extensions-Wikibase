<?php

/**
 * Maintenance script for permanently(!) deleting all Wikibase data.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : dirname( __FILE__ ) . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

class WikibaseUninstall extends Maintenance {

	public function __construct() {
		$this->mDescription = 'Delete the Wikibase data';

		parent::__construct();
	}

	public function execute() {
		echo "Are you really really sure you want to permanently delete all Wikibase data?? If so, type YES\n";

		if ( $this->readconsole() !== 'YES' ) {
			return;
		}

		echo "Delete tables as well? if so, type y\n";

		$command = $this->readconsole() === 'y' ? 'DROP' : 'TRUNCATE';

		$tables = array(
			'items',
			'items_per_site',
			'texts_per_lang',
			'aliases',
			'changes',
		);

		$dbw = wfGetDB( DB_MASTER );

		foreach ( $tables as $table ) {
			$name = "wb_$table";

			echo "$command table $name...";

			$dbw->query( $command . ' TABLE ' . $dbw->tableName( $name ), __METHOD__ );

			echo "done!\n";
		}
	}

}

$maintClass = 'WikibaseUninstall';
require_once( RUN_MAINTENANCE_IF_MAIN );
