<?php
namespace Wikibase;

/**
 * Utility class for collecting dispatch statistics.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DispatchStats {

	/**
	 * @var string
	 */
	protected $dispatchTableName;

	/**
	 * @var string
	 */
	protected $changesTableName;

	/**
	 * @var null|array
	 */
	protected $clientStates;

	/**
	 * @var null|object
	 */
	protected $changeStats;

	/**
	 * @var null|object
	 */
	protected $average;

	/**
	 * creates a new DispatchStats instance.
	 *
	 * Call load() before accessing any getters.
	 */
	public function __construct() {
		$this->dispatchTableName = 'wb_changes_dispatch';
		$this->changesTableName = 'wb_changes';

		$this->clientStates = null;
		$this->changeStats = null;

		$this->average = null;
	}

	/**
	 * Loads the current dispatch status from the database and calculates statistics.
	 * Before this method is called, the behavior of the getters is undefined.
	 *
	 * @return int the number of client wikis.
	 */
	public function load() {
		$db = wfGetDB( DB_SLAVE ); // XXX: use master?

		$this->changeStats = $db->selectRow(
			$this->changesTableName,
			array(
				'min( change_id ) as min_id',
				'max( change_id ) as max_id',
				'min( change_time ) as min_time',
				'max( change_time ) as max_time',
			),
			'',
			__METHOD__
		);

		$res = $db->select( $this->dispatchTableName,
			array( 'chd_site',
					'chd_db',
					'chd_seen',
					'chd_touched',
					'chd_lock',
					'chd_disabled',
			),
			array(
				'chd_disabled' => 0
			),
			__METHOD__,
			array(
				'ORDER BY' => 'chd_seen ASC'
			)
		);

		$this->average = new \stdClass();
		$this->average->chd_lag = 0;
		$this->average->chd_dist = 0;

		$this->clientStates = array();

		while ( $row = $res->fetchObject() ) {
			if ( $this->changeStats ) {
				$row->chd_lag = max( 0, (int)wfTimestamp( TS_UNIX, $this->changeStats->max_time )
					- (int)wfTimestamp( TS_UNIX, $row->chd_touched ) );

				$row->chd_dist = (int)$this->changeStats->max_id - $row->chd_seen;
			} else {
				// if there are no changes, there is no lag
				$row->chd_lag = 0;
				$row->chd_dist = 0;
			}

			$this->average->chd_lag += $row->chd_lag;
			$this->average->chd_dist += $row->chd_dist;

			$this->clientStates[] = $row;
		}

		$n = count( $this->clientStates );

		if ( $n > 0 ) {
			$this->average->chd_lag = intval( $this->average->chd_lag / $n );
			$this->average->chd_dist = intval( $this->average->chd_dist / $n );
		}

		return $n;
	}

	public function hasStats() {
		return is_array( $this->clientStates );
	}

	/**
	 * Returns the dispatch state for all client wikis.
	 * The state for each wiki is returned as an object containing the following fields:
	 *
	 * * chd_site: the client wiki's site ID
	 * * chd_lag:  seconds since that client was last touched by a dispatcher
	 * * chd_dist: number of changes not yet dispatched to that client
	 * * chd_lock: the name of the lock currently in effect for that wiki
	 *
	 * @return array|null A list of objects representing the dispatch state
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
		return count( $this->clientStates );
	}

	/**
	 * Returns a dispatch status object for the client wiki
	 * that was updated most recently.
	 *
	 * See getClientStates() for the structure of the status object.
	 *
	 * @return object
	 */
	public function getFreshest() {
		return end( $this->clientStates );
	}

	/**
	 * Returns a dispatch status object for the client wiki
	 * that was updated least recently.
	 *
	 * See getClientStates() for the structure of the status object.
	 *
	 * @return object
	 */
	public function getStalest() {
		return reset( $this->clientStates );
	}


	/**
	 * Returns a dispatch status object for the client wiki
	 * that represents the median in terms of dispatch lag.
	 *
	 * See getClientStates() for the structure of the status object.
	 *
	 * @return object
	 */
	public function getMedian() {
		$n = $this->getClientCount();

		if ( $n == 0 ) {
			return null;
		}

		$i = (int)floor( $n / 2 );
		return $this->clientStates[$i];
	}

	/**
	 * Returns a pseudo-status object representing the average (mean) dispatch
	 * lag. The status object has the following fields:
	 *
	 * * chd_lag:  seconds since that client was last touched by a dispatcher
	 * * chd_dist: number of changes not yet dispatched to that client
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

		foreach ( $this->clientStates as $row ) {
			if ( $row->chd_lock !== null ) {
				$c++;
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
		return intval( $this->changeStats->max_id );
	}

	/**
	 * Returns the oldest change ID from the changes table.
	 *
	 * @return int
	 */
	public function getMinChangeId() {
		return intval( $this->changeStats->min_id );
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

