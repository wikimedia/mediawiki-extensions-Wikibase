<?php

namespace Wikibase\Repo\ParserOutput;

use LinkBatch;
use ParserOutput;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorFactory;

/**
 * Finds linked entities on an Entity and add the links to ParserOutput.
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Kreuz
 */
class ReferencedEntitiesDataUpdater implements EntityParserOutputDataUpdater {

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var EntityIdParser
	 */
	private $externalEntityIdParser;

	/**
	 * @var EntityReferenceExtractorFactory
	 */
	private $entityReferenceExtractorFactory;

	/**
	 * @param EntityReferenceExtractorFactory $entityReferenceExtractorFactory
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EntityIdParser $externalEntityIdParser Parser for external entity IDs (usually URIs)
	 *        into EntityIds. Such external entity IDs may be used for units in QuantityValues, for
	 *        calendar models in TimeValue, and for the reference globe in GlobeCoordinateValues.
	 */
	public function __construct(
		EntityReferenceExtractorFactory $entityReferenceExtractorFactory,
		EntityTitleLookup $entityTitleLookup,
		EntityIdParser $externalEntityIdParser
	) {
		$this->entityTitleLookup = $entityTitleLookup;
		$this->externalEntityIdParser = $externalEntityIdParser;
		$this->entityReferenceExtractorFactory = $entityReferenceExtractorFactory;
	}

	public function processEntity( EntityDocument $entity ) {
		$this->entityIds = $this->entityReferenceExtractorFactory->extractEntityIds( $entity );
	}

	public function updateParserOutput( ParserOutput $parserOutput ) {
		/**
		 * Needed and used in EntityParserOutputGenerator, for getEntityInfo, to allow this data to
		 * be accessed later in processing.
		 *
		 * @see EntityParserOutputGenerator::getEntityInfo
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
