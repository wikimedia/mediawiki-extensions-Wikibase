<?php

namespace Wikibase\Repo\ParserOutput;

use DataValues\DataValue;
use DataValues\UnboundedQuantityValue;
use LinkBatch;
use ParserOutput;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Finds linked entities on an Entity and add the links to ParserOutput.
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Kreuz
 */
class ReferencedEntitiesDataUpdater implements StatementDataUpdater, SiteLinkDataUpdater {

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var EntityIdParser
	 */
	private $externalEntityIdParser;

	/**
	 * @var EntityId[] Associative array mapping entity id serializations to EntityId objects.
	 */
	private $entityIds = [];

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
	 * @return EntityId[] Numerically indexed non-sparse array.
	 */
	public function getEntityIds() {
		return array_values( $this->entityIds );
	}

	/**
	 * Finds linked entities in a Statement.
	 *
	 * @param Statement $statement
	 */
	public function processStatement( Statement $statement ) {
		foreach ( $statement->getAllSnaks() as $snak ) {
			$this->processSnak( $snak );
		}
	}

	private function processSnak( Snak $snak ) {
		$propertyId = $snak->getPropertyId();
		$this->entityIds[$propertyId->getSerialization()] = $propertyId;

		if ( $snak instanceof PropertyValueSnak ) {
			$this->processDataValue( $snak->getDataValue() );
		}
	}

	private function processDataValue( DataValue $dataValue ) {
		if ( $dataValue instanceof EntityIdValue ) {
			$entityId = $dataValue->getEntityId();
			$this->entityIds[$entityId->getSerialization()] = $entityId;
		} elseif ( $dataValue instanceof UnboundedQuantityValue ) {
			$unitUri = $dataValue->getUnit();
			$this->processUri( $unitUri );
		}

		// TODO: EntityIds from GlobeCoordinateValue's globe URI (Wikidata, not local item URI!)
		// TODO: EntityIds from TimeValue's calendar URI (Wikidata, not local item URI!)
	}

	/**
	 * @param string $uri
	 */
	private function processUri( $uri ) {
		try {
			$entityId = $this->externalEntityIdParser->parse( $uri );
			$this->entityIds[$entityId->getSerialization()] = $entityId;
		} catch ( EntityIdParsingException $ex ) {
			// noop
		}
	}

	public function processSiteLink( SiteLink $siteLink ) {
		foreach ( $siteLink->getBadges() as $badge ) {
			$this->entityIds[$badge->getSerialization()] = $badge;
		}
	}

	public function updateParserOutput( ParserOutput $parserOutput ) {
		/**
		 * Needed and used in EntityParserOutputGenerator, for getEntityInfo, to allow this data to
		 * be accessed later in processing.
		 *
		 * @see EntityParserOutputGenerator::getEntityInfo
		 * @fixme Use self::getEntityIds instead.
		 */
		$parserOutput->setExtensionData( 'referenced-entities', $this->entityIds );
		$this->addLinksToParserOutput( $parserOutput );
	}

	private function addLinksToParserOutput( ParserOutput $parserOutput ) {
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

}
