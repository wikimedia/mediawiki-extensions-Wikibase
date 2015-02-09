<?php

namespace Wikibase;
use Maintenance;

/**
 * Prune the Wikibase changes table to a maximum number of entries.
 *
 */
$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';
require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Prune the Wikibase changes table to a maximum number of entries.
 *
 */
class PruneChanges extends Maintenance {

	/**
	 * @var int the minimum number of seconds to keep changes for.
	 */
	private $keepSeconds = 0;

	/**
	 * @var int the minimum number of seconds after dispatching to keep changes for.
	 */
	private $graceSeconds = 0;

	/**
	 * @var bool whether the dispatch time should be ignored
	 */
	private $ignoreDispatch = false;

	/**
	 * @var int The amount of rows to delete at once.
	 */
	private $pruneLimit = 0;

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Prune the Wikibase changes table to a maximum number of entries";

		$this->addOption( 'number-of-days', 'Keep changes at least N days (deprecated).', false, true, 'n' );
		$this->addOption( 'keep-days',  'Keep changes at least N days.', false, true, 'd' );
		$this->addOption( 'keep-hours', 'Keep changes at least N hours.', false, true, 'h' );
		$this->addOption( 'keep-minutes', 'Keep changes at least N minutes.', false, true, 'm' );
		$this->addOption( 'grace-minutes', 'Keep changes at least N more minutes after they have been dispatched.', false, true, 'g' );

		$this->addOption( 'limit', 'Only prune up to N rows at once.', false, true, 'l' );

		$this->addOption( 'force', 'Run regardless of whether the PID file says it is running already.',
						 false, false, 'f' );

		$this->addOption( 'ignore-dispatch', 'Ignore whether changes have been dispatched or not.',
						false, false, 'D' );
	}

	public function execute() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$force = $this->getOption( 'force', false );
		$pidfile = Utils::makePidFilename( 'WBpruneChanges', wfWikiID() );

		if ( !Utils::getPidLock( $pidfile, $force ) ) {
			$this->output( date( 'H:i:s' ) . " already running, exiting\n" );
			exit( 5 );
		}

		$this->ignoreDispatch = $this->getOption( 'ignore-dispatch', false );

		$this->keepSeconds = 0;
		$this->keepSeconds += intval( $this->getOption( 'number-of-days', 0 ) ) * 24 * 60 * 60;
		$this->keepSeconds += intval( $this->getOption( 'keep-days', 0 ) ) * 24 * 60 * 60;
		$this->keepSeconds += intval( $this->getOption( 'keep-hours', 0 ) ) * 60 * 60;
		$this->keepSeconds += intval( $this->getOption( 'keep-minutes', 0 ) ) * 60;

		if ( $this->keepSeconds === 0 ) {
			// one day
			$this->keepSeconds = 1 * 24 * 60 * 60;
		}

		$this->graceSeconds = 0;
		$this->graceSeconds += intval( $this->getOption( 'grace-minutes', 0 ) ) * 60;

		if ( $this->graceSeconds === 0 ) {
			// one hour
			$this->graceSeconds = 1 * 60 * 60;
		}

		$this->pruneLimit = intval( $this->getOption( 'limit', 25000 ) );

		$until = $this->getCutoffTimestamp();
		$until = $this->limitDeletions( $until );
		$this->output( date( 'H:i:s' ) . " pruning entries older than "
			. wfTimestamp( TS_ISO_8601, $until ) . "\n" );

		$deleted = $this->pruneChanges( $until );
		$this->output( date( 'H:i:s' ) . " $deleted rows pruned.\n" );

		unlink( $pidfile ); // delete lockfile on normal exit
	}

	/**
	 * Calculates the timestamp up to which changes can be pruned.
	 *
	 * @return int Timestamp up to which changes can be pruned (as Unix period).
	 */
	private function getCutoffTimestamp() {
		$until = time() - $this->keepSeconds;

		if ( !$this->ignoreDispatch ) {
			$dbr = wfGetDB( DB_SLAVE );
			$row = $dbr->selectRow(
				array ( 'wb_changes_dispatch', 'wb_changes' ),
				'min(change_time) as timestamp',
				array(
					'chd_disabled' => 0,
					'chd_seen = change_id'
				),
				__METHOD__
			);

			if ( isset( $row->timestamp ) ) {
				$dispatched = wfTimestamp( TS_UNIX, $row->timestamp ) - $this->graceSeconds;

				$until = min( $until, $dispatched );
			}
		}

		return $until;
	}

	/**
	 * @param int $until
	 *
	 * @return int
	 */
	private function limitDeletions( $until ) {
		$dbr = wfGetDB( DB_SLAVE );
		$changeTime = $dbr->selectField(
			'wb_changes',
			'change_time',
			array( 'change_time < ' . $dbr->addQuotes( wfTimestamp( TS_MW, $until ) ) ),
			__METHOD__,
			array(
				'OFFSET' => $this->pruneLimit,
				'ORDER BY' => 'change_time ASC',
			)
		);

		return $changeTime ? intval( $changeTime ) : $until;
	}

	/**
	 * Prunes all changes older than $until from the changes table.
	 *
	 * @param int $until
	 *
	 * @return int the number of changes deleted.
	 */
	private function pruneChanges( $until ) {
		$dbw = wfGetDB( DB_MASTER );

		$dbw->delete(
			'wb_changes',
			array( 'change_time < ' . $dbw->addQuotes( wfTimestamp( TS_MW, $until ) ) ),
			__METHOD__
		);

		return $dbw->affectedRows();
	}

}

$maintClass = 'Wikibase\PruneChanges';
require_once( RUN_MAINTENANCE_IF_MAIN );
