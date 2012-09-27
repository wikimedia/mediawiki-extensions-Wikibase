<?php

namespace Wikibase;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for deleting all Wikibase data.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DeleteAllData extends \Maintenance {

	public function __construct() {
		$this->mDescription = 'Delete the Wikidata data';

		parent::__construct();
	}

	public function execute() {
		$quick = $_SERVER['argc'] > 1 && $_SERVER['argv'][1] == '--yes-im-sure-maybe';

		if ( !$quick ) {
			echo "Are you really really sure you want to delete all the Wikibase data?? If so, type DELETE\n";

			if ( $this->readconsole() !== 'DELETE' ) {
				return;
			}
		}

		$report = function( $message ) {
			echo $message;
		};

		wfRunHooks( 'WikibaseDeleteData', array( $report ) );

		$dbw = wfGetDB( DB_MASTER );

		$tables = array(
			'wb_changes',
		);

		// TODO: put in client
		if ( defined( 'WBC_VERSION' ) ) {
			$tables = array_merge( $tables, array(
				'wbc_item_usage',
				'wbc_query_usage',
				'wbc_entity_cache',
				'wbc_items_per_site',
			) );
		}

		foreach ( $tables as $table ) {
			echo "Emptying table $table...";

			$dbw->delete( $dbw->tableName( $table ), '*', __METHOD__ );

			echo "done!\n";
		}
	}

}

$maintClass = 'Wikibase\DeleteAllData';
require_once( RUN_MAINTENANCE_IF_MAIN );
