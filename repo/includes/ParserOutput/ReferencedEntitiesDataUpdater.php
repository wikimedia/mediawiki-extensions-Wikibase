<?php

namespace Wikibase\Repo\ParserOutput;

use LinkBatch;
use ParserOutput;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractor;

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
	 * @var EntityReferenceExtractor
	 */
	private $entityReferenceExtractor;

	/**
	 * @var EntityId[]
	 */
	private $entityIds = [];

	/**
	 * @param EntityReferenceExtractor $entityReferenceExtractor
	 * @param EntityTitleLookup $entityTitleLookup
	 */
	public function __construct(
		EntityReferenceExtractor $entityReferenceExtractor,
		EntityTitleLookup $entityTitleLookup
	) {
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityReferenceExtractor = $entityReferenceExtractor;
	}

	public function processEntity( EntityDocument $entity ) {
		$this->entityIds = $this->entityReferenceExtractor->extractEntityIds( $entity );
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
