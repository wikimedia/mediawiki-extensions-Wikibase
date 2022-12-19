<?php

namespace Wikibase\Repo\ParserOutput;

use MediaWiki\Cache\LinkBatchFactory;
use ParserOutput;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
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
class ReferencedEntitiesDataUpdater implements EntityParserOutputUpdater {

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var EntityReferenceExtractor
	 */
	private $entityReferenceExtractor;

	/**
	 * @var LinkBatchFactory
	 */
	private $linkBatchFactory;

	/**
	 * @param EntityReferenceExtractor $entityReferenceExtractor
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param LinkBatchFactory $linkBatchFactory
	 */
	public function __construct(
		EntityReferenceExtractor $entityReferenceExtractor,
		EntityTitleLookup $entityTitleLookup,
		LinkBatchFactory $linkBatchFactory
	) {
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityReferenceExtractor = $entityReferenceExtractor;
		$this->linkBatchFactory = $linkBatchFactory;
	}

	public function updateParserOutput( ParserOutput $parserOutput, EntityDocument $entity ) {
		$entityIds = $this->entityReferenceExtractor->extractEntityIds( $entity );

		$this->addLinksToParserOutput( $parserOutput, $entityIds );
	}

	private function addLinksToParserOutput( ParserOutput $parserOutput, array $entityIds ) {
		$linkBatch = $this->linkBatchFactory->newLinkBatch();
		$linkBatch->setCaller( __METHOD__ );

		foreach ( $entityIds as $entityId ) {
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
