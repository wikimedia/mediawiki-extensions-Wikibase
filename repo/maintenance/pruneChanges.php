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

		if ( $force === true ) {
			// Make sure this script only runs once
			$pidfileName = 'WBpruneChanges_' . wfWikiID() . ".pid";
			// Let's see if we have a /var/run directory and if we can write to it (i.e. we're root)
			if ( is_dir( '/var/run/' ) && is_writable( '/var/run/' ) ) {
				$pidfile = '/var/run/' . $pidfileName;
			}
			// else use the temporary directory
			else {
				$pidfilePath = str_replace( '\\', '/', sys_get_temp_dir() );
				$pidfile = $pidfilePath . '/' . $pidfileName;
			}
			if ( file_exists( $pidfile ) ) {
				$pid = file_get_contents( $pidfile );
				if ( $this->checkPID( $pid ) === false ) {
					self::msg( 'Process has died, restarting...' );
					file_put_contents( $pidfile, getmypid() ); // update lockfile
				} else {
					self::msg( 'PID is still alive, cannot run twice' );
					exit;
				}
			} else {
				file_put_contents( $pidfile, getmypid() ); // create lockfile
			}
		}

		$this->pruneChanges( $numDays, $force);

		self::msg( 'Done, unlinking PID and exiting' );
		unlink( $pidfile ); // delete lockfile on normal exit
	}

	public function pruneChanges( $numDays ) {
		$dbw = wfGetDB( DB_MASTER );

		$dbw->delete(
			'wb_changes',
			array( "change_time < DATE_SUB(NOW(), INTERVAL $numDays DAY)" ),
			__METHOD__
		);
	}

	/**
	 * Check for running PID
	 *
	 * @param int $pid
	 * @return boolean
	 */
	protected function checkPID ( $pid ) {
		// Are we anything but Windows, i.e. some kind of Unix?
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) !== 'WIN' ) {
			return posix_getsid( $pid );
		}
		// Welcome to Redmond
		else {
			$processes = explode( "\n", shell_exec( "tasklist.exe" ) );
			if ( $processes !== false && count( $processes ) > 0 ) {
				foreach( $processes as $process ) {
					if( strlen( $process ) > 0
						&& ( strpos( "Image Name", $process ) === 0
						|| strpos( "===", $process ) === 0 ) ) {
						continue;
					}
					$matches = false;
					preg_match( "/^(\D*)(\d+).*$/", $process, $matches );
					$processid = 0;
					if ( $matches !== false && count ($matches) > 1 ) {
						$processid = $matches[ 2 ];
					}
					if ( $processid === $pid ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Handle a message (ie display and logging)
	 *
	 * @param string $message
	 */
	public static function msg( $message ) {
		echo date( 'H:i:s' ) . ' ' . $message . "\n";
	}

}

$maintClass = 'Wikibase\PruneChanges';
require_once( RUN_MAINTENANCE_IF_MAIN );