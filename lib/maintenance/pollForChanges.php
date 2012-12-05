<?php

namespace Wikibase;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script that polls for Wikibase changes in the shared wb_changes table
 * and triggers a hook to invoke the code that needs to handle these changes.
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
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig
 */
class PollForChanges extends \Maintenance {

	/**
	 * @var ChangesTable
	 */
	protected $changes;

	/**
	 * @var integer
	 */
	protected $lastChangeId;

	/**
	 * @var integer
	 */
	protected $pollLimit;

	/**
	 * @var integer
	 */
	protected $startTime;

	/**
	 * @var integer
	 */
	protected $sleepInterval;

	/**
	 * @var integer
	 */
	protected $continueInterval;

	/**
	 * @var bool
	 */
	protected $done = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->mDescription =
			'Maintenance script that polls for Wikibase changes in the shared wb_changes table
			and triggers a hook to invoke the code that needs to handle these changes.';

		$this->addOption( 'verbose', "Print change objects to be processed" );

		$this->addOption( 'once', "Processes one batch and exits" );

		$this->addOption( 'all', "Processes changes until no more are pending, then exits" );

		$this->addOption( 'since', 'Process changes since timestamp. Timestamp should be given in the form of "yesterday",'
			. ' "14 September 2012", "1 week 2 days 4 hours 2 seconds ago",'
			. ' "last Monday" or any other format supported by strtotime()', false, true );

		$this->addOption( 'startid', "Start polling at the given change_id value", false, true );

		$this->addOption( 'polllimit', "Maximum number of changes to handle in one batch", false, true );

		$this->addOption( 'sleepinterval', "Interval (in seconds) to sleep after processing all pending changes.", false, true );

		$this->addOption( 'continueinterval', "Interval (in seconds) to sleep after processing a full batch.", false, true );

		parent::__construct();
	}

	/**
	 * Maintenance script entry point.
	 */
	public function execute() {
		if ( !defined( 'WBL_VERSION' ) ) {
			// Since people might waste time debugging odd errors when they forget to enable the extension. BTDT.
			die( 'WikibaseLib has not been loaded.' );
		}

		$this->changes = ChangesTable::singleton();

		$this->lastChangeId = (int)$this->getOption( 'startid', 0 );
		$this->pollLimit = (int)$this->getOption( 'polllimit', Settings::get( 'pollDefaultLimit' ) );
		$this->sleepInterval = (int)$this->getOption( 'sleepinterval', Settings::get( 'pollDefaultInterval' ) ) * 1000;
		$this->continueInterval = (int)$this->getOption( 'continueinterval', Settings::get( 'pollContinueInterval' ) ) * 1000;

		$this->startTime = (int)strtotime( $this->getOption( 'since', 0 ) );

		// Make sure this script only runs once
		$pidfile = Utils::makePidFilename( 'WBpollForChanges', wfWikiID() );
		if ( !Utils::getPidLock( $pidfile, false ) ) {
			$this->msg( "already running, exiting." );
			exit( 5 );
		}

		$changesWiki = Settings::get( 'changesDatabase' );

		if ( $changesWiki ) {
			self::msg( "Polling changes from $changesWiki." );
		} else {
			self::msg( "Polling changes from local wiki." );
		}

		while ( !$this->done ) {
			$ms = $this->doPoll();
			usleep( $ms * 1000 );
		}

		unlink( $pidfile ); // delete lockfile on normal exit
	}

	/**
	 * Do a poll operation, finding all new changes.
	 *
	 * @return integer The amount of milliseconds the script should sleep before doing the next poll.
	 */
	protected function doPoll() {
		$changes = $this->changes->select(
			null,
			$this->getContinuationConds(),
			array(
				'LIMIT' => $this->pollLimit,
				'ORDER BY ' . $this->changes->getPrefixedField( 'id' ) . ' ASC'
			),
			__METHOD__
		);

		$changeCount = $changes->count();
		assert( 'is_int( $changeCount ) /* $changeCount must be int */' );

		if ( $changeCount == 0 ) {
			if ( $this->getOption( 'verbose' ) ) {
				self::msg( 'No new changes were found' );
			}
		}
		else {
			if ( $changeCount == 1 ) {
				self::msg( 'One new change was found' );
			} else {
				self::msg( $changeCount . ' new changes were found' );
			}
			$changes = iterator_to_array( $changes );

			try {
				if ( $this->getOption( 'verbose' ) ) {
					/**
					 * @var Change $change
					 */
					foreach ( $changes as $change ) {
							$fields = $change->getFields(); //@todo: Fixme: add getFields() to the interface, or provide getters!
							preg_match( '/wikibase-(item|property|query)~(.+)$/', $fields[ 'type' ], $matches );
							$type = ucfirst( $matches[ 2 ] ); // This is the verb (like "update" or "add")
							$object = $matches[ 1 ]; // This is the object (like "item" or "property").

							self::msg(
								'Processing change ' . $change->getId() . ' (' . $change->getTime() . '): '
									. $type . ' for '. $object . ' ' . $change->getObjectId()
							);
					}
				}

				ChangeHandler::singleton()->handleChanges( $changes );
			}
			catch ( \Exception $ex ) {
				$ids = array_map( function( Change $change ) { return $change->getId(); }, $changes );
				self::msg( 'FAILED TO HANDLE CHANGES ' . implode( ', ', $ids ) . ': ' . $ex->getMessage() );
			}

			$this->lastChangeId = array_pop( $changes )->getId();
		}

		if ( $changeCount >= $this->pollLimit ) {
			$sleepFor = $this->continueInterval;
		} else {
			$sleepFor = $this->sleepInterval;

			if ( $this->getOption( 'verbose' ) ) {
				self::msg( 'All changes processed.' );
			}

			if ( $this->getOption( 'all' ) ) {
				$this->done = true;
			}
		}

		if ( $this->getOption( 'once' ) ) {
			$this->done = true;
		}

		return $sleepFor;
	}

	/**
	 * @return array
	 */
	protected function getContinuationConds() {
		$conds = array();

		$dbr = wfGetDB( DB_SLAVE );

		if ( $this->lastChangeId === 0 && $this->startTime !== 0 ) {
			$conds[] = 'time > ' . $dbr->addQuotes( wfTimestamp( TS_MW, $this->startTime ) );
		}

		if ( $this->lastChangeId !== false ) {
			$conds[] = 'id > ' . $dbr->addQuotes( $this->lastChangeId );
		}

		return $conds;
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

$wgHooks['WikibasePollHandle'] = function( Change $change ) {
	PollForChanges::msg( 'Handling change with id ' . $change->getId() );
};

$maintClass = 'Wikibase\PollForChanges';
require_once( RUN_MAINTENANCE_IF_MAIN );
