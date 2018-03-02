<?php

namespace Wikibase;

/**
 * Utility class for collecting dispatch statistics.
 * Note that you must call load() before accessing any getters.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class DispatchStats {

	/**
	 * @var object[]|null
	 */
	private $clientStates = null;

	/**
	 * @var object|null
	 */
	private $changeStats = null;

	/**
	 * @var object|null
	 */
	private $average = null;

	/**
	 * Loads the current dispatch status from the database and calculates statistics.
	 * Before this method is called, the behavior of the getters is undefined.
	 *
	 * @param int|string $now Timestamp to consider the current time. Mostly useful for testing.
	 *
	 * @return int the number of client wikis.
	 */
	public function load( $now = 0 ) {
		$db = wfGetDB( DB_REPLICA ); // XXX: use master?

		$now = wfTimestamp( TS_UNIX, $now );

		$this->changeStats = $db->selectRow(
			'wb_changes',
			[
				'min( change_id ) as min_id',
				'max( change_id ) as max_id',
				'min( change_time ) as min_time',
				'max( change_time ) as max_time',
			],
			'',
			__METHOD__
		);

		$res = $db->select(
			[
				'wb_changes_dispatch',
				'wb_changes'
			],
			[ 'chd_site',
					'chd_db',
					'chd_seen',
					'chd_touched',
					'chd_lock',
					'chd_disabled',
					'change_time',
			],
			[
				'chd_disabled' => 0
			],
			__METHOD__,
			[
				'ORDER BY' => 'chd_seen ASC'
			],
			[
				'wb_changes' => [ 'LEFT JOIN', 'chd_seen = change_id' ]
			]
		);

		$this->average = new \stdClass();
		$this->average->chd_untouched = 0;
		$this->average->chd_pending = 0;
		$this->average->chd_lag = 0;

		$this->clientStates = [];

		foreach ( $res as $row ) {
			if ( $this->changeStats ) {
				// time between last dispatch and now
				$row->chd_untouched = max( 0, $now
					- (int)wfTimestamp( TS_UNIX, $row->chd_touched ) );

				// time between the timestamp of the last changed processed and the last change recorded.
				if ( $row->change_time === null ) {
					// the change was already pruned, lag is "big".
					$row->chd_lag = null;
				} else {
					$row->chd_lag = max( 0, (int)wfTimestamp( TS_UNIX, $this->changeStats->max_time )
						- (int)wfTimestamp( TS_UNIX, $row->change_time ) );
				}

				// number of changes that have not been processed yet
				$row->chd_pending = (int)$this->changeStats->max_id - $row->chd_seen;
			} else {
				// if there are no changes, there is no lag
				$row->chd_untouched = 0;
				$row->chd_pending = 0;
				$row->chd_lag = 0;
			}

			$this->average->chd_untouched += $row->chd_untouched;
			$this->average->chd_pending += $row->chd_pending;

			if ( $row->chd_lag === null || $this->average->chd_lag === null ) {
				$this->average->chd_lag = null;
			} else {
				$this->average->chd_lag += $row->chd_lag;
			}

			$this->clientStates[] = $row;
		}

		$n = count( $this->clientStates );

		if ( $n > 0 ) {
			$this->average->chd_untouched = (int)( $this->average->chd_untouched / $n );
			$this->average->chd_pending = (int)( $this->average->chd_pending / $n );
			$this->average->chd_lag = $this->average->chd_lag === null
				? null
				: (int)( $this->average->chd_lag / $n );
		}

		return $n;
	}

	/**
	 * @return bool
	 */
	public function hasStats() {
		return !empty( $this->clientStates );
	}

	/**
	 * Returns the dispatch state for all client wikis.
	 * The state for each wiki is returned as an object containing the following fields:
	 *
	 * * chd_site: the client wiki's site ID
	 * * chd_untouched:  seconds since that client was last touched by a dispatcher
	 * * chd_pending: number of changes not yet dispatched to that client
	 * * chd_lag: seconds between the timestamp of the last change that got dispatched
	 *            and the latest change recorded. May be null if some of the pending changes
	 *            have already been pruned. This indicates that the average could not be
	 *            determined, but the lag is large.
	 * * chd_lock: the name of the lock currently in effect for that wiki
	 *
	 * @return object[]|null A list of objects representing the dispatch state
	 *         for each client wiki.
	 */
	public function getClientStates() {
		return $this->clientStates;
	}

	/**
	 * Returns the number of active client wikis.
	 *
	 * @return int
	 */
	public function getClientCount() {
		return $this->clientStates ? count( $this->clientStates ) : 0;
	}

	/**
	 * Returns a dispatch status object for the client wiki
	 * that was updated most recently.
	 *
	 * See getClientStates() for the structure of the status object.
	 *
	 * @return object|null
	 */
	public function getFreshest() {
		return $this->clientStates ? end( $this->clientStates ) : null;
	}

	/**
	 * Returns a dispatch status object for the client wiki
	 * that was updated least recently.
	 *
	 * See getClientStates() for the structure of the status object.
	 *
	 * @return object|null
	 */
	public function getStalest() {
		return $this->clientStates ? reset( $this->clientStates ) : null;
	}

	/**
	 * Returns a dispatch status object for the client wiki
	 * that represents the median in terms of dispatch lag.
	 *
	 * See getClientStates() for the structure of the status object.
	 *
	 * @return object|null
	 */
	public function getMedian() {
		if ( empty( $this->clientStates ) ) {
			return null;
		}

		$i = (int)( count( $this->clientStates ) / 2 );
		return $this->clientStates[$i];
	}

	/**
	 * Returns a pseudo-status object representing the average (mean) dispatch
	 * lag. The status object has the following fields:
	 *
	 * * chd_untouched:  seconds since that client was last touched by a dispatcher
	 * * chd_pending: number of changes not yet dispatched to that client
	 * * chd_lag: seconds between the timestamp of the last change that got dispatched
	 *            and the latest change recorded. May be null if some of the pending changes
	 *            have already been pruned. This indicates that the average could not be
	 *            determined, but the lag is large.
	 *
	 * @return object
	 */
	public function getAverage() {
		return $this->average;
	}

	/**
	 * Returns the number of client wikis currently locked.
	 * Note that this does not probe the locks, so they may be stale.
	 *
	 * @return int
	 */
	public function getLockedCount() {
		$c = 0;

		if ( !empty( $this->clientStates ) ) {
			foreach ( $this->clientStates as $row ) {
				if ( $row->chd_lock !== null ) {
					$c++;
				}
			}
		}

		return $c;
	}

	/**
	 * Returns the newest change ID from the changes table.
	 *
	 * @return int
	 */
	public function getMaxChangeId() {
		return (int)$this->changeStats->max_id;
	}

	/**
	 * Returns the oldest change ID from the changes table.
	 *
	 * @return int
	 */
	public function getMinChangeId() {
		return (int)$this->changeStats->min_id;
	}

	/**
	 * Returns the newest timestamp from the changes table.
	 *
	 * @return string
	 */
	public function getMaxChangeTimestamp() {
		return $this->changeStats->max_time;
	}

	/**
	 * Returns the oldest timestamp from the changes table.
	 *
	 * @return string
	 */
	public function getMinChangeTimestamp() {
		return $this->changeStats->min_time;
	}

}
