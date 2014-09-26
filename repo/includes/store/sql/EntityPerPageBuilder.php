<?php

namespace Wikibase\Repo\Store\SQL;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\NamespaceUtils;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Store\EntityPerPage;

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
	 * @since 0.5
	 *
	 * @var array
	 */
	protected $contentModels;

	/**
	 * @param EntityPerPage $entityPerPageTable
	 * @param EntityIdParser $entityIdParser
	 * @param array $contentModels
	 */
	public function __construct(
		EntityPerPage $entityPerPageTable,
		EntityIdParser $entityIdParser,
		array $contentModels
	) {
		$this->entityPerPageTable = $entityPerPageTable;
		$this->entityIdParser = $entityIdParser;
		$this->contentModels = $contentModels;
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
	 * @param MessageReporter $reporter
	 */
	public function setReporter( MessageReporter $reporter ) {
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
				array( 'page', 'redirect', 'wb_entity_per_page' ),
				array( 'page_title', 'page_id', 'rd_title' ),
				$this->getQueryConds( $lastPageSeen ),
				__METHOD__,
				array( 'LIMIT' => $this->batchSize, 'ORDER BY' => 'page_id' ),
				array(
					'redirect' => array( 'LEFT JOIN', 'rd_from = page_id' ),
					'wb_entity_per_page' => array( 'LEFT JOIN', 'page_id = epp_page_id' )
				)
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
		global $wgContentHandlerUseDB;

		$conds = array(
			'page_namespace' => NamespaceUtils::getEntityNamespaces(),
			'page_id > ' . (int) $lastPageSeen,
		);

		if ( $wgContentHandlerUseDB ) {
			$conds['page_content_model'] = $this->contentModels;
		}

		if ( $this->rebuildAll === false ) {
			$conds[] = 'epp_page_id IS NULL';
		}

		return $conds;
	}

	/**
	 * @param string $idString
	 *
	 * @return EntityId|null
	 */
	private function tryParseId( $idString ) {
		try {
			return $this->entityIdParser->parse( $idString );
		} catch ( EntityIdParsingException $e ) {
			wfDebugLog( __CLASS__, 'Invalid entity id ' . $idString );
		}

		return null;
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
			$this->updateEntry( $pageRow );

			$lastPageSeen = $pageRow->page_id;
		}

		return $lastPageSeen;
	}

	/**
	 * @param object $pageRow
	 */
	private function updateEntry( $pageRow ) {
		// Derive the entity id from the page title
		$entityId = $this->tryParseId( $pageRow->page_title );

		if ( !$entityId ) {
			return;
		}

		// Derive the target id from the redirect target title
		$targetId = $pageRow->rd_title === null ? null : $this->tryParseId( $pageRow->rd_title );

		if ( $this->rebuildAll === true ) {
			$this->entityPerPageTable->deleteEntity( $entityId );
		}

		$pageId = (int)$pageRow->page_id;

		if ( $targetId ) {
			$this->entityPerPageTable->addRedirectPage( $entityId, $pageId, $targetId );
		} else {
			$this->entityPerPageTable->addEntityPage( $entityId, $pageId );
		}
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
			list( , $maxLag ) = $lb->getMaxLag();
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
