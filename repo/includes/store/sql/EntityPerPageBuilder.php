<?php
namespace Wikibase;

/**
 * Utility class for rebuilding the wb_entity_per_page table.
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
class EntityPerPageBuilder {

	/**
	 * @since 0.4
	 *
	 * @var EntityPerPage $entityPerPageTable
	 */
	protected $entityPerPageTable;

	/**
	 * @since 0.4
	 *
	 * @var MessageReporter $reporter
	 */
	protected $reporter;

	/**
	 * The batch size, giving the number of rows to be updated in each database transaction.
	 *
	 * @var int
	 */
	protected $batchSize = 100;

	/**
	 * Rebuild the entire table or only missing pages
	 *
	 * @var boolean
	 */
	protected $rebuildAll = true;

	/**
	 * @param EntityPerPage $entityPerPageTable
	 */
	public function __construct( EntityPerPage $entityPerPageTable ) {
		$this->entityPerPageTable = $entityPerPageTable;
	}

	/**
	 * @param int $batchSize
	 */
	public function setBatchSize( $batchSize ) {
		$this->batchSize = $batchSize;
	}

	/**
	 * @since 0.4
	 *
	 * @param boolean $rebuildAll
	 */
	public function setRebuildAll( $rebuildAll ) {
		$this->rebuildAll = $rebuildAll;
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
	 */
	public function rebuild() {
		if ( $this->rebuildAll === true ) {
			$this->entityPerPageTable->clear();
		}

		$dbw = wfGetDB( DB_MASTER );

		$lastPageSeen = 0;
		$numPages = 1;

		$this->report( 'Start rebuild...' );

		while ( $numPages > 0 ) {
			$this->waitForSlaves( $dbw );

			$pages = $dbw->select(
				array( 'page', 'wb_entity_per_page' ),
				array( 'page_title', 'page_id' ),
				array(
					'page_namespace' => NamespaceUtils::getEntityNamespaces(),
					'page_id > ' . $lastPageSeen,
					'epp_page_id IS NULL'
				),
				__METHOD__,
				array( 'LIMIT' => $this->batchSize, 'ORDER BY' => 'page_id' ),
				array( 'wb_entity_per_page' => array( 'LEFT JOIN', 'page_id = epp_page_id' ) )
			);

			$numPages = $pages->numRows();

			if ( $numPages > 0 ) {
				$lastPageSeen = $this->rebuildPages( $pages );

				$this->report( "Processed $numPages pages up to $lastPageSeen." );
			}
		}

		$this->report( "Rebuild done." );

		return true;
	}

	/**
	 * Rebuilds EntityPerPageTable for specified pages
	 *
	 * @since 0.4
	 *
	 * @param \ResultWrapper $pages
	 *
	 * @return int
	 */
	protected function rebuildPages( $pages ) {
		$lastPageSeen = 0;
		$entityContentFactory = EntityContentFactory::singleton();

		foreach ( $pages as $pageRow ) {
			$id = EntityId::newFromPrefixedId( $pageRow->page_title );

			if ( $id !== null ) {
				$entityContent = $entityContentFactory->getFromId( $id, \Revision::RAW );

				if ( $entityContent !== null ) {
					$this->entityPerPageTable->addEntityContent( $entityContent );
				}
			}

			$lastPageSeen = $pageRow->page_id;
		}

		return $lastPageSeen;
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
