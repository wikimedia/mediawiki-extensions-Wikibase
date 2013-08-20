<?php

namespace Wikibase;

use DatabaseBase;
use Title;
use Wikibase\Client\WikibaseClient;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script that polls for Wikibase changes in the shared wb_changes table
 * and dispatches the relevant changes to any client wikis' job queues.
 *
 * @licence GNU GPL v2+
 */
class ChangeProcessor extends \Maintenance {

	public function execute() {
		$title = Title::newFromText( 'Rome' );

		$table = WikibaseClient::getDefaultInstance()->getStore()->newChangesTable();
		$changes = $table->selectObjects( null, array(), array(), __METHOD__ );
		$generator = new ExternalChangeGenerator( 'testrepo' );
		$externalChange = $generator->getParamsFromEntityChange( $changes[0], $title, 'a change' );

		var_export( $externalChange );
	}

}

$maintClass = 'Wikibase\ChangeProcessor';
require_once( RUN_MAINTENANCE_IF_MAIN );
