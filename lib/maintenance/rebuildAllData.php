<?php

namespace Wikibase;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for rebuilding all secondary Wikibase data (ie indexes and caches).
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class RebuildAllData extends \Maintenance {

	public function __construct() {
		$this->mDescription = 'Rebuild the Wikidata data';

		parent::__construct();
	}

	public function execute() {
		$quick = $_SERVER['argc'] > 1 && $_SERVER['argv'][1] == '--yes-im-sure-maybe';

		if ( !$quick ) {
			echo "Are you really really sure you want to rebuild all the Wikibase data?? If so, type YES\n";

			if ( $this->readconsole() !== 'YES' ) {
				return;
			}
		}

		$report = function( $message ) {
			echo $message;
		};

		wfRunHooks( 'WikibaseRebuildData', array( $report ) );
	}

}

$maintClass = 'Wikibase\RebuildAllData';
require_once( RUN_MAINTENANCE_IF_MAIN );
