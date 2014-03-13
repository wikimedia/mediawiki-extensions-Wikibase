<?php
namespace Wikibase;

use MessageReporter;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * Utility class for rebuilding the wb_entity_per_page table.
 *
 * @since 0.4
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
	 * @var EntityContentFactory $entityContentFactory
	 */
	protected $entityContentFactory;

	/**
	 * @since 0.4
	 *
	 * @var EntityIdParser $entityIdParser
	 */
	protected $entityIdParser;

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
	 * Rebuild the entire table
	 *
	 * @var boolean
	 */
	protected $rebuildAll = false;

	/**
	 * @param EntityPerPage $entityPerPageTable
	 * @param EntityContentFactory $entityContentFactory
	 * @param EntityIdParser $entityIdParser
	 */
	public function __construct( EntityPerPage $entityPerPageTable, EntityContentFactory $entityContentFactory,
		EntityIdParser $entityIdParser ) {
		$this->entityPerPageTable = $entityPerPageTable;
		$this->entityContentFactory = $entityContentFactory;
		$this->entityIdParser = $entityIdParser;
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
		$dbw = wfGetDB( DB_MASTER );

		$lastPageSeen = 0;
		$numPages = 1;

		$this->report( 'Start rebuild...' );

		while ( $numPages > 0 ) {
			$this->waitForSlaves( $dbw );

			$pages = $dbw->select(
				array( 'page', 'wb_entity_per_page' ),
				array( 'page_title', 'page_id' ),
				$this->getQueryConds( $lastPageSeen ),
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
	 * Construct query conditions
	 *
	 * @since 0.4
	 *
	 * @param int $lastPageSeen
	 *
	 * @return array
	 */
	protected function getQueryConds( $lastPageSeen ) {
		$conds = array(
			'page_namespace' => NamespaceUtils::getEntityNamespaces(),
			'page_id > ' . $lastPageSeen
		);

		if ( $this->rebuildAll === false ) {
			$conds[] = 'epp_page_id IS NULL';
		}

		return $conds;
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

		foreach ( $pages as $pageRow ) {
			try {
				$entityId = $this->entityIdParser->parse( $pageRow->page_title );
			} catch ( EntityIdParsingException $e ) {
				wfDebugLog( __CLASS__, __METHOD__ . ': entity id in page row is invalid.' );
				continue;
			}

			$entityContent = $this->entityContentFactory->getFromId( $entityId, \Revision::RAW );

			if ( $this->rebuildAll === true ) {
				$this->entityPerPageTable->deleteEntityPage( $entityId, $entityContent->getTitle()->getArticleID() );
			}

			if ( $entityContent !== null ) {
				$this->entityPerPageTable->addEntityPage( $entityId, $entityContent->getTitle()->getArticleID() );
			} else {
				$this->entityPerPageTable->deleteEntity( $entityId );
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
