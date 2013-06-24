<?php

namespace Wikibase;
use DatabaseBase;

/**
 * Utility class for rebuilding the term_search_key field.
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
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Daniel Kinzler
 */
class TermSearchKeyBuilder {

	/**
	 * @since 0.4
	 *
	 * @var TermSqlIndex $table
	 */
	protected $table;

	/**
	 * @since 0.4
	 *
	 * @var MessageReporter $reporter
	 */
	protected $reporter;

	/**
	 * Whether all keys should be updated, or only missing keys
	 *
	 * @var bool
	 */
	protected $all = true;

	/**
	 * Whether all keys should be updated, or only missing keys
	 *
	 * @var bool
	 */
	protected $fromId = 1;

	/**
	 * The batch size, giving the number of rows to be updated in each database transaction.
	 *
	 * @var int
	 */
	protected $batchSize = 100;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param TermSqlIndex $table
	 */
	public function __construct( TermSqlIndex $table ) {
		$this->table = $table;
	}

	/**
	 * @return boolean
	 */
	public function getRebuildAll() {
		return $this->all;
	}

	/**
	 * @return int
	 */
	public function getBatchSize() {
		return $this->batchSize;
	}

	/**
	 * @return boolean
	 */
	public function getFromId() {
		return $this->fromId;
	}

	/**
	 * @param boolean $all
	 */
	public function setRebuildAll( $all ) {
		$this->all = $all;
	}

	/**
	 * @param int $batchSize
	 */
	public function setBatchSize( $batchSize ) {
		$this->batchSize = $batchSize;
	}

	/**
	 * @param boolean $fromId
	 */
	public function setFromId( $fromId ) {
		$this->fromId = $fromId;
	}

	/**
	 * Sets the reporter to use for reporting preogress.
	 *
	 * @param \MessageReporter $reporter
	 */
	public function setReporter( \MessageReporter $reporter ) {
		$this->reporter = $reporter;
	}

	/**
	 * Rebuild the search key field term_search_key from the source term_text field.
	 * Use the rebuildSearchKey.php maintenance script to invoke this from the command line.
	 *
	 * Database updates a batched into multiple transactions. Do not call this
	 * method whithin an (explicite) database transaction.
	 *
	 * @since 0.4
	 */
	public function rebuildSearchKey() {
		$dbw = $this->table->getWriteDb();

		$rowId = $this->fromId -1;

		$total = 0;

		while ( true ) {
			// Make sure we are not running too far ahead of the slaves,
			// as that would cause the site to be rendered read only.
			$this->waitForSlaves( $dbw );

			$dbw->begin();

			$terms = $dbw->select(
				$this->table->getTableName(),
				array(
					'term_row_id',
					'term_language',
					'term_text',
				),
				array(
					'term_row_id > ' . (int) $rowId,
					$this->all ? '1' : 'term_search_key = \'\'', // if not $all, only set missing keys
				),
				__METHOD__,
				array(
					'LIMIT' => $this->batchSize,
					'ORDER BY' => 'term_row_id ASC',
					'FOR UPDATE'
				)
			);

			$c = 0;
			$cError = 0;

			foreach ( $terms as $row ) {
				$key = $this->updateSearchKey( $dbw, $row->term_row_id, $row->term_text, $row->term_language );

				if ( $key === false ) {
					$this->report( "Unable to calculate search key for " . $row->term_text );
					$cError += 1;
				} else {
					$c+= 1;
				}

				$rowId = $row->term_row_id;
			}

			$dbw->commit();

			$this->report( "Updated $c search keys (skipped $cError), up to row $rowId." );
			$total += $c;

			if ( $c < $this->batchSize ) {
				// we are done.
				break;
			}
		}

		return $total;
	}

	/**
	 * Wait for slaves (quietly)
	 *
	 * @todo: this should be in the Database class.
	 * @todo: thresholds should be configurable
	 *
	 * @author Tim Starling (stolen from recompressTracked.php)
	 */
	protected function waitForSlaves() {
		$lb = wfGetLB(); //TODO: allow foreign DB, get from $this->table

		while ( true ) {
			list( $host, $maxLag ) = $lb->getMaxLag();
			if ( $maxLag < 2 ) {
				break;
			}

			$this->report( "Slaves are lagged by $maxLag seconds, sleeping..." );
			sleep( 5 );
			$this->report( "Resuming..." );
		}
	}

	/**
	 * Updates a single row with a newley calculated search key.
	 * The search key is calculated using Term::normalizeText().
	 *
	 * @see Term::normalizeText
	 *
	 * @since 0.4
	 *
	 * @param \DatabaseBase $dbw the database connection to use
	 * @param int $rowId the row to update
	 * @param string $text the term's text
	 * @param string $lang the term's language
	 *
	 * @return string|bool the search key, or false if no search key could be calculated.
	 */
	protected function updateSearchKey( \DatabaseBase $dbw, $rowId, $text, $lang ) {
		$key = Term::normalizeText( $text, $lang );

		if ( $key === '' ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": failed to normalized term: $text" );
			return false;
		}

		wfDebugLog( __CLASS__, __FUNCTION__ . ": row_id = $rowId, search_key = `$key`" );

		$dbw->update(
			$this->table->getTableName(),
			array(
				'term_search_key' => $key,
			),
			array(
				'term_row_id' => $rowId,
			),
			__METHOD__
		);

		return $key;
	}

	/**
	 * reports a message
	 *
	 * @since 0.4
	 *
	 * @param $msg
	 */
	protected function report( $msg ) {
		if ( $this->reporter ) {
			$this->reporter->reportMessage( $msg );
		}
	}

}