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
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityPerPageRebuilder {

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
	 * Which page id to start the rebuild?
	 *
	 * @var int
	 */
	protected $fromPageId = 1;

	/**
	 * The batch size, giving the number of rows to be updated in each database transaction.
	 *
	 * @var int
	 */
	protected $batchSize = 1000;

	/**
	 * Rebuild only the missing entities?
	 *
	 * @var boolean
	 */
	protected $onlyMissing = false;

	/**
	 * @return int
	 */
	public function getFromPageId() {
		return $this->fromPageId;
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
	public function getOnlyMissing() {
		return $this->onlyMissing;
	}

	/**
	 * @param boolean $onlyMissing
	 */
	public function setOnlyMissing( $onlyMissing ) {
		$this->onlyMissing = $onlyMissing;
	}

	/**
	 * @param int $batchSize
	 */
	public function setBatchSize( $batchSize ) {
		$this->batchSize = $batchSize;
	}

	/**
	 * @param int $fromPageId
	 */
	public function setFromPageId( $fromPageId ) {
		$this->fromPageId = $fromPageId;
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
	 * @since 0.4
	 *
	 * @param EntityPerPage $entityPerPage
	 */
	public function rebuild( EntityPerPage $entityPerPage ) {
		$dbw = wfGetDB( DB_MASTER );
		$begin = 0;
		$entityContentFactory = EntityContentFactory::singleton();

		do {
			$this->waitForSlaves( $dbw );

			$dbw->begin();

			$pages = $dbw->select(
				array( 'page' ),
				array( 'page_title' ),
				array( 'page_namespace' => NamespaceUtils::getEntityNamespaces() ),
				__METHOD__,
				array( 'LIMIT' => 1000, 'OFFSET' => $begin )
			);

			foreach ( $pages as $pageRow ) {
				$id = EntityId::newFromPrefixedId( $pageRow->page_title );

				if ( $id !== null ) {
					$entityContent = $entityContentFactory->getFromId( $id, \Revision::RAW );

					if ( $entityContent !== null ) {
						$entityPerPage->addEntityContent( $entityContent );
					}
				}
			}

			$dbw->commit();

			$begin += 1000;

			$this->report( "Processed pages up to $begin." );

		} while ( $pages->numRows() === 1000 );

		return true;
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
