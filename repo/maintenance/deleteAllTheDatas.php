<?php

/**
 * Maintenance scrtip for deleting all Wikibase data.
 *
 * @since 0.1
 *
 * @file DeleteAllTheDatas.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : dirname( __FILE__ ) . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

class DeleteAllTheDatas extends Maintenance {

	public function __construct() {
		$this->mDescription = 'Delete the Wikipedia Education Program data';

		parent::__construct();
	}

	public function execute() {
		echo "Are you really really sure you want to delete all the Wikibase data?? If so, type YES\n";

		if ( $this->readconsole() !== 'YES' ) {
			return;
		}

		$tables = array(
			'items',
			'items_per_site',
			'texts_per_lang',
		);

		$dbw = wfGetDB( DB_MASTER );

		foreach ( $tables as $table ) {
			$name = "wb_$table";

			echo "Truncating table $name...";

			$dbw->query( 'TRUNCATE TABLE ' . $dbw->tableName( $name ) );

			echo "done!\n";
		}

		echo "Deleting pages from Data NS...";

		$dbw->delete(
			'page',
			array( 'page_namespace' => 100 )
		);

		echo "done!\n";
	}

}

$maintClass = 'DeleteAllTheDatas';
require_once( RUN_MAINTENANCE_IF_MAIN );
