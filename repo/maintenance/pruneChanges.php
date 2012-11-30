<?php

namespace Wikibase;
use Maintenance;

/**
 * Prune the Wikibase changes table to a maximum number of entries.
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
 * Prune the Wikibase changes table to a maximum number of entries.
 *
 * @ingroup Maintenance
 */
class PruneChanges extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Prune the Wikibase changes table to a maximum number of entries";

		$this->addOption( 'number-of-days', 'Number of days to keep entries in the table after the '
						. 'maintenance script has been run (default: 7)', false, true, 'n' );
		$this->addOption( 'force', 'Run regardless of whether the PID file says it is running already.',
						 false, false, 'f' );
	}

	public function execute() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$numDays = intval( $this->getOption( 'number-of-days', 7 ) );
		$force = $this->getOption( 'force', false );
		$pidfile = Utils::makePidFilename( 'WBpruneChanges', wfWikiID() );

		if ( !Utils::getPidLock( $pidfile, $force ) ) {
			$this->output( date( 'H:i:s' ) . " already running, exiting\n" );
			exit( 5 );
		}

		$this->pruneChanges( $numDays, $force );

		$this->output( date( 'H:i:s' ) . " done, exiting\n" );
		unlink( $pidfile ); // delete lockfile on normal exit
	}

	public function pruneChanges( $numDays ) {
		$dbw = wfGetDB( DB_MASTER );

		$since = wfTimestamp( TS_MW, time() - $numDays * 24 * 60 * 60 );

		$this->output( date( 'H:i:s' ) . " pruning entries older than "
						. wfTimestamp( TS_ISO_8601, $since ) . "\n" );

		$dbw->delete(
			'wb_changes',
			array( "change_time < " . $dbw->addQuotes( $since ) ), // added after request
			__METHOD__
		);

		$rows = $dbw->affectedRows();
		$this->output( date( 'H:i:s' ) . " $rows rows pruned\n" );
	}

}

$maintClass = 'Wikibase\PruneChanges';
require_once( RUN_MAINTENANCE_IF_MAIN );
