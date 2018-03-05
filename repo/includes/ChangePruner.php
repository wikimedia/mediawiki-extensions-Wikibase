<?php

namespace Wikibase\Repo;

use InvalidArgumentException;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\NullMessageReporter;

/**
 * Handles pruning wb_changes table, used by pruneChanges maintenance script.
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ChangePruner {

	/**
	 * @var int
	 */
	private $batchSize;

	/**
	 * @var int the minimum number of seconds to keep changes for.
	 */
	private $keepSeconds;

	/**
	 * @var int the minimum number of seconds after dispatching to keep changes for.
	 */
	private $graceSeconds;

	/**
	 * @var bool whether the dispatch time should be ignored
	 */
	private $ignoreDispatch;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @param int $batchSize
	 * @param int $keepSeconds
	 * @param int $graceSeconds
	 * @param bool $ignoreDispatch
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $batchSize, $keepSeconds, $graceSeconds, $ignoreDispatch ) {
		if ( !is_int( $batchSize ) || $batchSize <= 0 ) {
			throw new InvalidArgumentException( '$batchSize must be a positive integer' );
		}

		if ( !is_int( $keepSeconds ) || $keepSeconds < 0 ) {
			throw new InvalidArgumentException( '$keepSeconds must be a non-negative integer' );
		}

		if ( !is_int( $graceSeconds ) || $graceSeconds < 0 ) {
			throw new InvalidArgumentException( '$graceSeconds must be a non-negative integer' );
		}

		$this->batchSize = $batchSize;
		$this->keepSeconds = $keepSeconds;
		$this->graceSeconds = $graceSeconds;
		$this->ignoreDispatch = $ignoreDispatch;

		$this->messageReporter = new NullMessageReporter();
	}

	/**
	 * Prunes the wb_changes table.
	 */
	public function prune() {
		while ( true ) {
			wfWaitForSlaves();

			$until = $this->getCutoffTimestamp();

			$this->messageReporter->reportMessage(
				date( 'H:i:s' ) . " pruning entries older than "
				. wfTimestamp( TS_ISO_8601, $until )
			);

			$affected = $this->pruneChanges( $until );

			$this->messageReporter->reportMessage( date( 'H:i:s' ) . " $affected rows pruned." );

			if ( $affected === 0 ) {
				break;
			}
		}
	}

	/**
	 * Calculates the timestamp up to which changes can be pruned.
	 *
	 * @return string Timestamp up to which changes can be pruned (as MediaWiki concatenated string
	 * timestamp).
	 */
	private function getCutoffTimestamp() {
		$until = time() - $this->keepSeconds;

		if ( !$this->ignoreDispatch ) {
			$dbr = wfGetDB( DB_REPLICA );
			$row = $dbr->selectRow(
				[ 'wb_changes_dispatch', 'wb_changes' ],
				'min(change_time) as timestamp',
				[
					'chd_disabled' => 0,
					'chd_seen = change_id'
				],
				__METHOD__
			);

			if ( isset( $row->timestamp ) ) {
				$dispatched = wfTimestamp( TS_UNIX, $row->timestamp ) - $this->graceSeconds;

				$until = min( $until, $dispatched );
			}
		}

		$limitedTimestamp = $this->limitCutoffTimestamp( wfTimestamp( TS_MW, $until ) );

		// Add one second just to make sure we delete at least one second worth of data
		// as sometimes there are more edits in a single second than $this->batchSize
		// (the peak on Wikidata is almost 550).
		return wfTimestamp( TS_MW, wfTimestamp( TS_UNIX, $limitedTimestamp ) + 1 );
	}

	/**
	 * Changes the cutoff timestamp to not affect more than $this->batchSize
	 * rows, if needed.
	 *
	 * @param string $until MediaWiki concatenated string timestamp
	 *
	 * @return string MediaWiki concatenated string timestamp
	 */
	private function limitCutoffTimestamp( $until ) {
		$dbr = wfGetDB( DB_REPLICA );
		$changeTime = $dbr->selectField(
			'wb_changes',
			'change_time',
			[ 'change_time < ' . $dbr->addQuotes( $until ) ],
			__METHOD__,
			[
				'OFFSET' => $this->batchSize - 1,
				'ORDER BY' => 'change_time ASC',
			]
		);

		return $changeTime ?: $until;
	}

	/**
	 * Prunes all changes older than $until from the changes table.
	 *
	 * @param string $until MediaWiki concatenated string timestamp
	 *
	 * @return int the number of changes deleted.
	 */
	private function pruneChanges( $until ) {
		$dbw = wfGetDB( DB_MASTER );

		$dbw->delete(
			'wb_changes',
			[ 'change_time < ' . $dbw->addQuotes( $until ) ],
			__METHOD__
		);

		return $dbw->affectedRows();
	}

	/**
	 * @return MessageReporter
	 */
	public function getMessageReporter() {
		return $this->messageReporter;
	}

	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

}
