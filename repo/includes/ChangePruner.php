<?php

namespace Wikibase\Repo;

use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\NullMessageReporter;

/**
 * Handles pruning wb_changes table, used by pruneChanges maintenance script.
 *
 * @licence GNU GPL v2+
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
	 */
	public function __construct( $batchSize, $keepSeconds, $graceSeconds, $ignoreDispatch ) {
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
		while( true ) {
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
			$dbr = wfGetDB( DB_SLAVE );
			$row = $dbr->selectRow(
				array( 'wb_changes_dispatch', 'wb_changes' ),
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

		return $this->limitCutoffTimestamp( wfTimestamp( TS_MW, $until ) );
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
		$dbr = wfGetDB( DB_SLAVE );
		$changeTime = $dbr->selectField(
			'wb_changes',
			'change_time',
			array( 'change_time < ' . $dbr->addQuotes( $until ) ),
			__METHOD__,
			array(
				'OFFSET' => $this->batchSize,
				'ORDER BY' => 'change_time ASC',
			)
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
			array( 'change_time < ' . $dbw->addQuotes( $until ) ),
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

	/**
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

}
