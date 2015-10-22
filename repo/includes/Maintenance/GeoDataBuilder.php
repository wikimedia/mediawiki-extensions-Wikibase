<?php

namespace Wikibase\Repo\Maintenance;

use Content;
use DeferredUpdates;
use GeoDataHooks;
use LinksUpdate;
use MWException;
use ParserOutput;
use Title;
use WikiPage;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\EntityContent;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Store\EntityIdPager;

/**
 * Helper class for populating the geo_tags table in the GeoData
 * extension for Wikibase content.
 *
 * For practical reasons, page_props is also updated for each
 * item or property so that page_image page prop is populated.
 * Both page_props update and populating GeoData need to have
 * the content parsed and obtain this data from ParserOutput.
 *
 * This class is intended as a temporary, targeted alternative
 * to running refreshLinks.php maintenance script, for populating
 * GeoData and page_image page prop. It would be better if
 * refreshLinks could be more flexible and have the option
 * of only performing certain updates, etc., and/or be more
 * reusable instead of duplicating some bits of code here.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class GeoDataBuilder {

	/**
	 * @var EntityIdPager
	 */
	private $entityIdPager;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	/**
	 * @var MessageReporter
	 */
	private $reporter;

	/**
	 * @param EntityIdPager $entityIdPager
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param EntityStore $entityStore
	 * @param MessageReporter $reporter
	 */
	public function __construct(
		EntityIdPager $entityIdPager,
		EntityTitleLookup $entityTitleLookup,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		EntityStore $entityStore,
		MessageReporter $reporter
	) {
		$this->entityIdPager = $entityIdPager;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->entityStore = $entityStore;
		$this->reporter = $reporter;
	}

	/**
	 * @param EntityId $startId Start EntityId for beginning rebuild.
	 * @param int $batchSize
	 * @param int $limit
	 */
	public function rebuild( EntityId $startId, $batchSize, $limit ) {
		$entityCount = 0;
		$limitReached = false;

		while ( $entityIds = $this->entityIdPager->fetchIds( $batchSize ) ) {
			foreach ( $entityIds as $entityId ) {
				$lastId = $entityId;

				// @fixme the script is agnostic to different entity types
				// since we don't yet filter by entity type, etc.
				if ( $entityId->getNumericId() < $startId->getNumericId() ) {
					continue;
				}

				$entityCount++;
				$this->processEntityId( $entityId );

				if ( $limit > 0 && $entityCount >= $limit ) {
					$limitReached = true;
				}
			}

			$this->reporter->reportMessage(
				"Processed $entityCount entities up to " . $lastId->getSerialization()
			);

			if ( $limitReached === true ) {
				break;
			}
		}
	}

	/**
	 * @param EntityId $entityId
	 */
	private function processEntityId( EntityId $entityId ) {
		$content = $this->loadContent( $entityId );

		if ( $content === null ) {
			// $content could not be loaded or accessed
			$this->reporter->reportMessage(
				'ERROR: Failed to load page content for ' . $entityId->getSerialization()
			);

			return;
		}

		$title = $this->entityTitleLookup->getTitleForId( $entityId );

		if ( $this->isRelevant( $content, $title ) ) {
			// ParserOutput contains geodata and the page_image page prop, if relevant
			// for the given content.
			// Also, we skip generating html since it is not needed here.
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
	 * @param EntityId $entityId
	 *
	 * @return Content|null
	 */
	private function loadContent( EntityId $entityId ) {
		try {
			$page = $this->entityStore->getWikiPageForEntity( $entityId );
		} catch ( MWException $ex ) {
			// $page does not exist or other error
			$this->reporter->reportMessage(
				'ERROR: Page not found for ' . $title->getPrefixedText()
			);

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
			$this->reporter->reportMessage(
				'ERROR: Failed to load entity for '. $title->getPrefixedText()
			);

			return false;
		}

		if ( !$entity instanceof StatementListProvider ) {
			return false;
		}

		return $this->hasRelevantProperty( $entity->getStatements() );
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

}
