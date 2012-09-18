<?php

namespace Wikibase;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script that polls for Wikibase changes in the shared wb_changes table
 * and triggers a hook to invoke the code that needs to handle these changes.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
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
	 * Constructor.
	 */
	public function __construct() {
		$this->mDescription =
			'Maintenance script that polls for Wikibase changes in the shared wb_changes table
			and triggers a hook to invoke the code that needs to handle these changes.';

		$this->addOption( 'verbose', "Print change objects to be processed" );

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
		$pidfileName = wfWikiID() . ".pid";
		// Let's see if we have a /var/run directory and if we can write to it (i.e. we're root)
		if ( is_dir( '/var/run/' ) && is_writable( '/var/run/' ) ) {
			$pidfile = '/var/run/' . $pidfileName;
		// else use the temporary directory
		} else {
			$pidfilePath = str_replace( '\\', '/', sys_get_temp_dir() );
			$pidfile = $pidfilePath . '/' . $pidfileName;
		}
		if ( file_exists( $pidfile ) ) {
			$pid = file_get_contents( $pidfile );
			if ( $this->checkPID( $pid ) === false ) {
				self::msg( 'Process has died! Restarting...' );
				file_put_contents( $pidfile, getmypid() ); // update lockfile
			} else {
				self::msg( 'PID is still alive! Cannot run twice!' );
				exit;
			}
		} else {
			file_put_contents( $pidfile, getmypid() ); // create lockfile
		}

		while ( true ) {
			$ms = $this->doPoll();
			usleep( $ms * 1000 );
		}
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

		if ( $changeCount == 0 ) {
			self::msg( 'No new changes were found' );
		}
		else {
			self::msg( $changeCount . ' new changes were found' );

			$changes = iterator_to_array( $changes );

			try {
				if ( $this->getOption( 'verbose' ) ) {
					foreach ( $changes as $change ) {
							$fields = $change->getFields();
							preg_match( '/wikibase-(item|[^~-]+)[-~](.+)$/', $fields[ 'type' ], $matches );
							$type = ucfirst( $matches[ 2 ] ); // This is the verb (like "update" or "add")
							$object = $matches[ 1 ]; // This is the object (like "item" or "property").
							self::msg( 'Processing change: '. $type . ' for '. $object . ' ' .$fields[ 'id' ] );
						}
						ChangeHandler::singleton()->handleChanges( array( $change ) );
				} else {
					ChangeHandler::singleton()->handleChanges( $changes );
				}
			}
			catch ( \Exception $ex ) {
				$ids = array_map( function( Change $change ) { return $change->getId(); }, $changes );
				self::msg( 'FAILED TO HANDLE CHANGES ' . implode( ', ', $ids ) . ': ' . $ex->getMessage() );
			}

			$this->lastChangeId = array_pop( $changes )->getId();
		}

		return $changeCount === $this->pollLimit ? $this->continueInterval : $this->sleepInterval;
	}

	/**
	 * @return array
	 */
	protected function getContinuationConds() {
		$conds = array();

		if ( $this->lastChangeId === 0 && $this->startTime !== 0 ) {
			$conds[] = 'time > ' . wfGetDB( DB_SLAVE )->addQuotes( wfTimestamp( TS_MW, $this->startTime ) );
		}

		if ( $this->lastChangeId !== false ) {
			$conds[] = 'id > ' . $this->lastChangeId;
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

	/**
	 * Check for running PID
	 *
	 * @param int $pid
	 * @return boolean
	 */
	protected function checkPID ( $pid ) {
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) !== 'WIN' ) {
			// Are we anything but Windows, i.e. some kind of Unix?
			return posix_getsid( $pid );
		} else {
			// Welcome to Redmond
			$processes = explode( "\n", shell_exec( "tasklist.exe" ) );
			if ( $processes !== false && count( $processes ) > 0 ) {
				foreach( $processes as $process ) {
					if( strlen( $process ) > 0
						&& ( strpos( "Image Name", $process ) === 0
						|| strpos( "===", $process ) === 0 ) )
						continue;
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

}

$wgHooks['WikibasePollHandle'] = function( Change $change ) {
	PollForChanges::msg( 'Handling change with id ' . $change->getId() );
};

$maintClass = 'Wikibase\PollForChanges';
require_once( RUN_MAINTENANCE_IF_MAIN );
