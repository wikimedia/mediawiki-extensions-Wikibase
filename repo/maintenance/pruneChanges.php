<?php

namespace Wikibase\Repo\Maintenance;

use Maintenance;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';
require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GPL-2.0-or-later
 */
class PruneChanges extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription(
			'Deprecated maintenance script that no longer does anything. Do not use.'
		);

		$this->addOption( 'number-of-days', 'Unused.', false, true, 'n' );
		$this->addOption( 'keep-days', 'Unused.', false, true, 'd' );
		$this->addOption( 'keep-hours', 'Unused.', false, true, 'h' );
		$this->addOption( 'keep-minutes', 'Unused.', false, true, 'm' );
		$this->addOption( 'grace-minutes', 'Unused.', false, true, 'g' );
		$this->addOption( 'force', 'Unused.', false, false, 'f' );
		$this->addOption( 'ignore-dispatch', 'Unused.', false, false, 'D' );
	}

	public function execute() {
		$this->fatalError( 'This maintenance script no longer does anything. Please stop running it.', 3 );
	}

}

$maintClass = PruneChanges::class;
require_once RUN_MAINTENANCE_IF_MAIN;
