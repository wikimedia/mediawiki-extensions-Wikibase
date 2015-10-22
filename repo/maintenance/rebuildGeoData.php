<?php

namespace Wikibase\Repo\Maintenance;

use Content;
use DeferredUpdates;
use GeoDataHooks;
use LinksUpdate;
use Maintenance;
use MWException;
use Page;
use ParserOutput;
use Title;
use WikiPage;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\EntityContent;
use Wikibase\Repo\Store\EntityIdPager;
use Wikibase\Repo\Store\EntityPerPage;
use Wikibase\Repo\Store\SQL\EntityPerPageIdPager;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for updating GeoData and PageImages page prop.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RebuildGeoData extends Maintenance {

	/**
	 * @var EntityPerPage
	 */
	private $entityPerPage;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var int|null
	 */
	private $startId = null;

	public function __construct() {
		parent::__construct();

		$this->mDescription = 'Updates GeoData and PageImages page prop.';

		$this->addOption( 'batch-size', "Number of entities to process per batch", false, true );
		$this->addOption( 'limit', "Maximum number of entities processed", false, true );
		$this->addOption( 'start-id', "Entity ID to start from", false, true );
	}

	/**
	 * Do the actual work.
	 */
	public function execute() {
		if ( !class_exists( 'GeoDataHooks' ) ) {
			$this->error( 'GeoData extension must be enabled for this script to run.', 1 );
		}

		$this->setServices();
		$this->setStartIdFromOption();

		$this->processEntities(
			$this->newEntityIdPager(),
			(int)$this->getOption( 'batch-size', 100 ),
			(int)$this->getOption( 'limit', 0 )
		);

		$this->output( "Done\n" );
	}

	private function setServices() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$this->entityPerPage = $wikibaseRepo->getStore()->newEntityPerPage();
		$this->entityTitleLookup = $wikibaseRepo->getEntityTitleLookup();
		$this->propertyDataTypeLookup = $wikibaseRepo->getPropertyDataTypeLookup();
		$this->entityIdParser = $wikibaseRepo->getEntityIdParser();
	}

	private function setStartIdFromOption() {
		if ( $this->hasOption( 'start-id' ) ) {
			try {
				$this->startId = $this->entityIdParser->parse( $this->getOption( 'start-id' ) );
			} catch ( EntityIdParsingException $ex ) {
				$this->error( "Invalid start-id. Expected an id, such as 'Q1'.", 1 );
			}
		}
	}

	/**
	 * @param EntityIdPager $idPager
	 * @param int $batchSize
	 * @param int $limit
	 */
	private function processEntities( EntityIdPager $idPager, $batchSize, $limit ) {
		$entityCount = 0;

		while ( $ids = $idPager->fetchIds( $batchSize ) ) {
			foreach ( $ids as $id ) {
				if ( $this->startId instanceof EntityId && $this->isEntityIdBelowStartId( $id ) ) {
					continue;
				}

				$entityCount++;
				$this->processEntityId( $id );

				if ( $limit > 0 && $entityCount >= $limit ) {
					return;
				}
			}
		}

		$this->output( "Processed $entityCount entities\n" );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return boolean
	 */
	private function isEntityIdBelowStartId( EntityId $entityId ) {
		return $entityId->getNumericId() < $this->startId->getNumericId();
	}

	/**
	 * @param EntityId $entityId
	 */
	private function processEntityId( EntityId $entityId ) {
		$title = $this->entityTitleLookup->getTitleForId( $entityId );
		$titleText = $title->getPrefixedText();

		$content = $this->loadContent( $title );

		if ( $content === null ) {
			// $content could not be loaded or accessed
			$this->error( "Failed to load page content for $titleText.\n" );
			return;
		}

		if ( $this->isRelevant( $content, $title ) ) {
			$this->output( "Processing $titleText\n" );

			// skip generating html
			$parserOutput = $content->getParserOutput( $title, null, null, false );
			$linksUpdate = new LinksUpdate( $title, $parserOutput );

			$this->updateGeoData( $linksUpdate );
			$this->updatePageProps( $linksUpdate );
		}
	}

	/**
	 * @param LinksUpdate $linksUpdate
	 */
	private function updateGeoData( LinksUpdate $linksUpdate ) {
		GeoDataHooks::onLinksUpdate( $linksUpdate );
	}

	/**
	 * @param LinksUpdate $linksUpdate
	 */
	private function updatePageProps( LinksUpdate $linksUpdate ) {
		$existing = $this->getExistingProperties( $linksUpdate->mId );

		$propertiesDeletes = $linksUpdate->getPropertyDeletions( $existing );

		$linksUpdate->incrTableUpdate(
			'page_props',
			'pp',
			$propertiesDeletes,
			$linksUpdate->getPropertyInsertions( $existing )
		);
	}

	/**
	 * Code is borrowed from LinksUpdate in core
	 * @fixme make LinksUpdate more reusable, such as code for updating page props.
	 *
	 * @return array Array of property names and values
	 */
	private function getExistingProperties( $pageId ) {
		$dbr = wfGetDB( DB_MASTER );

		$res = $dbr->select(
			'page_props',
			array( 'pp_propname', 'pp_value' ),
			array( 'pp_page' => $pageId ),
			__METHOD__,
			array()
		);

		$arr = array();

		foreach ( $res as $row ) {
			$arr[$row->pp_propname] = $row->pp_value;
		}

		return $arr;
	}

	/**
	 * @param Title $title
	 *
	 * @return Content|null
	 */
	private function loadContent( Title $title ) {
		try {
			$page = WikiPage::factory( $title );
		} catch ( MWException $ex ) {
			// $page does not exist or other error
			$this->error( "Page not found for " . $title->getPrefixedText() . "\n" );
			return;
		}

		return $page->getContent();
	}

	/**
	 * @param EntityContent $entityContent
	 * @param Title $title
	 *
	 * @return boolean
	 */
	private function isRelevant( EntityContent $content, Title $title ) {
		try {
			$entity = $content->getEntity();
		} catch ( MWException $ex ) {
			// normally happens if EntityContent is a redirect, though we filter these
			// out when generting the EntityIdPager so shouldn't happen.
			$this->error( 'Failed to load entity for ' . $title->getPrefixedText() . "\n" );
			return false;
		}

		if ( !$entity instanceof StatementListProvider ) {
			$this->error( "Entity is not a StatementListProvider\n" );
			return false;
		}

		$statements = $entity->getStatements();

		if ( $statements->isEmpty() ) {
			return false;
		}

		return $this->hasRelevantProperty( $statements );
	}

	/**
	 * @param StatementList $statements
	 *
	 * @return boolean
	 */
	private function hasRelevantProperty( StatementList $statements ) {
		$propertyIds = $statements->getPropertyIds();

		foreach ( $propertyIds as $propertyId ) {
			try {
				$dataType = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $propertyId );
			} catch ( PropertyDataTypeLookupException $ex ) {
				// property not found, skip
				continue;
			}

			if ( $dataType === 'commonsMedia' || $dataType === 'globe-coordinate' ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return EntityIdPager
	 */
	private function newEntityIdPager() {
		return new EntityPerPageIdPager(
			$this->entityPerPage,
			null, // any entity type, @todo could specify StatementListProvider types
			EntityPerPage::NO_REDIRECTS
		);
	}

}

$maintClass = 'Wikibase\Repo\Maintenance\RebuildGeoData';
require_once RUN_MAINTENANCE_IF_MAIN;
