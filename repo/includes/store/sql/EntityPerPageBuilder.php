<?php
namespace Wikibase;

use DatabaseBase;
use MessageReporter;
use Revision;
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
	 * @var EntityContentFactory $entityContentFactory
	 */
	private $entityContentFactory;

	/**
	 * @var EntityIdParser $entityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityPerPageBuilderPagesFinder $pagesFinder
	 */
	private $pagesFinder;

	/**
	 * @var string[] $entityNamespaces
	 */
	private $entityNamespaces;

	/**
	 * @var MessageReporter $reporter
	 */
	private $reporter;

	/**
	 * @var int
	 */
	private $lastPageSeen;

	/**
	 * @param EntityContentFactory $entityContentFactory
	 * @param EntityIdParser $entityIdParser
	 * @param EntityPerPageBuilderPagesFinder $pagesFinder
	 * @param string[] $entityNamespaces
	 */
	public function __construct( EntityContentFactory $entityContentFactory,
		EntityIdParser $entityIdParser, EntityPerPageBuilderPagesFinder $pagesFinder,
		array $entityNamespaces
	) {
		$this->entityContentFactory = $entityContentFactory;
		$this->entityIdParser = $entityIdParser;
		$this->pagesFinder = $pagesFinder;
		$this->entityNamespaces = $entityNamespaces;
	}

	/**
	 * Sets the reporter to use for reporting progress.
	 *
	 * @param MessageReporter $reporter
	 */
	public function setReporter( MessageReporter $reporter ) {
		$this->reporter = $reporter;
	}

	/**
	 * @since 0.4
	 */
	public function rebuild( EntityPerPage $entityPerPage, $rebuildAll, $batchSize = 100 ) {
		$this->lastPageSeen = 0;
		$numPages = 1;

		$this->report( 'Start rebuild...' );

		while ( $numPages > 0 ) {
			$this->waitForSlaves();

			$pageSeenBefore = $this->lastPageSeen;
			$pages = $this->pagesFinder->getPages( $this->lastPageSeen, $batchSize );
			$numPages = $pages->numRows();

			if ( $numPages > 0 ) {
				$entityPerPage = $this->rebuildPages( $entityPerPage, $pages, $rebuildAll );

				$this->report( "Processed $numPages pages up to {$this->lastPageSeen}." );
			}

			if ( $this->lastPageSeen === $pageSeenBefore ) {
				break;
			}
		}

		$this->report( "Rebuild done." );

		return $entityPerPage;
	}

	/**
	 * Rebuilds EntityPerPageTable for specified pages
	 *
	 * @since 0.4
	 *
	 * @param EntityPerPage $entityPerPage
	 * @param \ResultWrapper $pages
	 * @param boolean $rebuildAll
	 *
	 * @return int
	 */
	protected function rebuildPages( EntityPerPage $entityPerPage, $pages, $rebuildAll ) {
		while( $pageRow = $pages->fetchRow() ) {
			$this->lastPageSeen = $pageRow['page_id'];

			if ( !$this->isEntityPage( $pageRow ) ) {
				$this->report( "Skipped " . $pageRow['page_title'] . ": not entity content" );
				continue;
			}

			$entityId = $this->entityIdParser->parse( $pageRow['page_title'] );
			$entityContent = $this->entityContentFactory->getFromId( $entityId, Revision::RAW );

			if ( $rebuildAll === true ) {
				$entityPerPage->deleteEntityById( $entityId );
			}

			if ( $entityContent !== null ) {
				$entityPerPage->addEntityContent( $entityContent );
			} else {
				$entityPerPage->removeEntity( $entityId );
			}
		}

		return $entityPerPage;
	}

	private function isEntityPage( $pageRow ) {
		$contentModel = $pageRow['page_content_model'];
		$entityContentModels = $this->entityContentFactory->getEntityContentModels();

		if ( $contentModel !== null && !in_array( $contentModel, $entityContentModels ) ) {
			return false;
		}

		return in_array( $pageRow['page_namespace'], $this->entityNamespaces );
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
