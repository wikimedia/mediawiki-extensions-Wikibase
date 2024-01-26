<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use HashSiteStore;
use LogicException;
use MediaWiki\Site\Site;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item as ReadModelItem;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemAliasesRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemDescriptionsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Infrastructure\SiteLinksReadModelConverter;

/**
 * @license GPL-2.0-or-later
 */
class InMemoryItemRepository implements ItemRetriever, ItemLabelsRetriever, ItemDescriptionsRetriever, ItemAliasesRetriever, ItemUpdater {
	use StatementReadModelHelper;

	public const EN_WIKI_SITE_ID = 'enwiki';
	public const DE_WIKI_SITE_ID = 'dewiki';
	public const EN_WIKI_URL_PREFIX = 'https://en.wikipedia.org/wiki/';
	public const DE_WIKI_URL_PREFIX = 'https://de.wikipedia.org/wiki/';

	private array $items = [];
	private array $latestRevisionData = [];

	public function addItem( Item $item ): void {
		if ( !$item->getId() ) {
			throw new LogicException( 'Test item must have an ID.' );
		}

		$this->items[$item->getId()->getSerialization()] = $item;
	}

	public function getLatestRevisionId( ItemId $id ): int {
		return $this->latestRevisionData["$id"]['revId'];
	}

	public function getLatestRevisionTimestamp( ItemId $id ): string {
		return $this->latestRevisionData["$id"]['revTime'];
	}

	public function getLatestRevisionEditMetadata( ItemId $id ): EditMetadata {
		return $this->latestRevisionData["$id"]['editMetadata'];
	}

	public function getItem( ItemId $itemId ): ?Item {
		return $this->items[$itemId->getSerialization()] ?? null;
	}

	public function getLabels( ItemId $itemId ): ?Labels {
		return $this->items["$itemId"] ? $this->convertToReadModel( $this->items["$itemId"] )->getLabels() : null;
	}

	public function getDescriptions( ItemId $itemId ): ?Descriptions {
		return $this->items["$itemId"] ? $this->convertToReadModel( $this->items["$itemId"] )->getDescriptions() : null;
	}

	public function getAliases( ItemId $itemId ): ?Aliases {
		return $this->items["$itemId"] ? $this->convertToReadModel( $this->items["$itemId"] )->getAliases() : null;
	}

	public function update( Item $item, EditMetadata $editMetadata ): ItemRevision {
		$this->items[$item->getId()->getSerialization()] = $item;
		$revisionData = [
			'revId' => rand(),
			// using the real date/time here is a bit dangerous, but should be ok as long as revId is also checked.
			'revTime' => date( 'YmdHis' ),
			'editMetadata' => $editMetadata,
		];
		$this->latestRevisionData[$item->getId()->getSerialization()] = $revisionData;

		return new ItemRevision( $this->convertToReadModel( $item ), $revisionData['revTime'], $revisionData['revId'] );
	}

	private function convertToReadModel( Item $item ): ReadModelItem {
		return new ReadModelItem(
			Labels::fromTermList( $item->getLabels() ),
			Descriptions::fromTermList( $item->getDescriptions() ),
			Aliases::fromAliasGroupList( $item->getAliasGroups() ),
			$this->newSitelinksReadModelConverter()->convert( $item->getSiteLinkList() ),
			new StatementList( ...array_map(
				[ $this->newStatementReadModelConverter(), 'convert' ],
				iterator_to_array( $item->getStatements() )
			) )
		);
	}

	private function newSitelinksReadModelConverter(): SiteLinksReadModelConverter {
		$enSite = new Site();
		$enSite->setGlobalId( self::EN_WIKI_SITE_ID );
		$enSite->setLinkPath( self::EN_WIKI_URL_PREFIX . '$1' );
		$deSite = new Site();
		$deSite->setGlobalId( self::DE_WIKI_SITE_ID );
		$deSite->setLinkPath( self::DE_WIKI_URL_PREFIX . '$1' );

		return new SiteLinksReadModelConverter( new HashSiteStore( [ $enSite, $deSite ] ) );
	}

}
