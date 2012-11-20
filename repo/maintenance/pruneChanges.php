<?php

namespace Wikibase;
use Maintenance, Exception;

/**
 * Prune the Wikibase changes table to a maxium number of entries.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Maintenance
 */
$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';
require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Prune the Wikibase changes table to a maxium number of entries.
 *
 * @ingroup Maintenance
 */
class PruneChanges extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Prune the Wikibase changes table to a maxium number of entries";

		$this->addOption( 'number-of-days', 'Number of days to keep entries in the table after the maintenance script has been run', false, true, 'days' );
		$this->addOption( 'force', 'Run regardless of whether the PID file says it is running already.', false, false, 'f' );

	}

	public function execute() {
		$numDays = intval( $this->getOption( 'number-of-days', 7 ) );
		$force = $this->getOption( 'force', false );
		$pidfile = Utils::makePidFilename( 'WBpruneChanges', wfWikiID() );

		if ( Utils::isAlreadyRunning( $pidfile, $force ) === false ) {
			echo date( 'H:i:s' ) . " failed, exiting\n";
		}

		$this->pruneChanges( $numDays, $force);

		echo date( 'H:i:s' ) . " done, exiting\n";
		unlink( $pidfile ); // delete lockfile on normal exit
	}

	public function pruneChanges( $numDays ) {
		$dbw = wfGetDB( DB_MASTER );

		$dbw->delete(
			'wb_changes',
			array( "change_time < DATE_SUB(NOW(), INTERVAL " . intval( $numDays ) . " DAY)" ), // added after request
			__METHOD__
		);
	}

}

$maintClass = 'Wikibase\PruneChanges';
require_once( RUN_MAINTENANCE_IF_MAIN );