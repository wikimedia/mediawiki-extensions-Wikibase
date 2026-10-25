<?php

namespace Wikibase\Repo\ParserOutput;

use MediaWiki\Page\LinkBatchFactory;
use MediaWiki\Parser\ParserOutput;
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

		$titles = [];
		foreach ( $entityIds as $entityId ) {
			$title = $this->entityTitleLookup->getTitleForId( $entityId );
			if ( $title !== null ) {
				$linkBatch->addObj( $title );
				$titles[] = $title;
			}
		}

		$linkBatch->execute();

		foreach ( $titles as $title ) {
			$pageId = $title->getArticleID();
			if ( $pageId ) {
				$parserOutput->addLink( $title, $pageId );
			}
		}
	}

}
