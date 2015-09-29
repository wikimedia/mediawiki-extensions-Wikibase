<?php

namespace Wikibase\Repo\DataUpdates;

use DataValues\DataValue;
use DataValues\QuantityValue;
use LinkBatch;
use ParserOutput;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Finds linked entities on an Entity and add the links to ParserOutput.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ReferencedEntitiesDataUpdate implements SiteLinkDataUpdate, StatementDataUpdate {

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var EntityIdParser
	 */
	private $externalEntityIdParser;

	/**
	 * @var array
	 */
	private $entityIds = array();

	/**
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EntityIdParser $externalEntityIdParser Parser for external entity IDs (usually URIs)
	 *		into EntityIds. Such external entity IDs may be used for units in QuantityValues, for
	 *		calendar models in TimeValue, and for the reference globe in GlobeCoordinateValues.
	 */
	public function __construct(
		EntityTitleLookup $entityTitleLookup,
		EntityIdParser $externalEntityIdParser
	) {
		$this->entityTitleLookup = $entityTitleLookup;
		$this->externalEntityIdParser = $externalEntityIdParser;
	}

	/**
	 * Finds linked entities in a Statement.
	 *
	 * @param Statement $statement
	 */
	public function processStatement( Statement $statement ) {
		$snaks = $statement->getAllSnaks();
		$this->extractEntityIdsFromSnaks( $snaks );
	}

	/**
	 * @param Snak[] $snaks
	 */
	private function extractEntityIdsFromSnaks( array $snaks ) {
		foreach ( $snaks as $snak ) {
			$propertyId = $snak->getPropertyId();
			$this->entityIds[$propertyId->getSerialization()] = $propertyId;

			if ( $snak instanceof PropertyValueSnak ) {
				$dataValue = $snak->getDataValue();
				$this->addEntityIdsFromValue( $dataValue );
			}
		}
	}

	/**
	 * @param SiteLink $siteLink
	 */
	public function processSiteLink( SiteLink $siteLink ) {
		$this->entityIds = array_merge( $this->entityIds, $siteLink->getBadges() );
	}

	/**
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput ) {
		// needed and used in EntityParserOutputGenerator, for getEntityInfo,
		// to allow this data to be accessed later in processing.
		$parserOutput->setExtensionData( 'referenced-entities', $this->entityIds );
		$this->addEntityLinksToParserOutput( $parserOutput );
	}

	/**
	 * @param ParserOutput $parserOutput
	 */
	private function addEntityLinksToParserOutput( ParserOutput $parserOutput ) {
		$linkBatch = new LinkBatch();

		foreach ( $this->entityIds as $entityId ) {
			$linkBatch->addObj( $this->entityTitleLookup->getTitleForId( $entityId ) );
		}

		$pages = $linkBatch->doQuery();

		if ( $pages === false ) {
			return;
		}

		foreach ( $pages as $page ) {
			$title = Title::makeTitle( $page->page_namespace, $page->page_title );
			$parserOutput->addLink( $title, $page->page_id );
		}
	}

	/**
	 * @param DataValue $dataValue
	 */
	private function addEntityIdsFromValue( DataValue $dataValue ) {
		if ( $dataValue instanceof EntityIdValue ) {
			$entityId = $dataValue->getEntityId();
			$this->entityIds[$entityId->getSerialization()] = $entityId;
		} elseif ( $dataValue instanceof QuantityValue ) {
			$unitUri = $dataValue->getUnit();
			$this->addEntityIdsFromURI( $unitUri );
		}

		// TODO: EntityIds from GlobeCoordinateValue's globe URI (Wikidata, not local item URI!)
		// TODO: EntityIds from TimeValue's calendar URI (Wikidata, not local item URI!)
	}

	/**
	 * @param string $uri
	 */
	private function addEntityIdsFromURI( $uri ) {
		try {
			$entityId = $this->externalEntityIdParser->parse( $uri );
			$this->entityIds[$entityId->getSerialization()] = $entityId;
		} catch ( EntityIdParsingException $ex ) {
			// noop
		}
	}

}
