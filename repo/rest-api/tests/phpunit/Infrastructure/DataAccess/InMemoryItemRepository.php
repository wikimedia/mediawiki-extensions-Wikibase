<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use HashSiteStore;
use LogicException;
use MediaWiki\Site\Site;
use Wikibase\DataModel\Entity\Item as ItemWriteModel;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelinks;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemAliasesRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemCreator;
use Wikibase\Repo\RestApi\Domain\Services\ItemDescriptionsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\SitelinksRetriever;
use Wikibase\Repo\RestApi\Infrastructure\SitelinksReadModelConverter;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class InMemoryItemRepository implements
	ItemRetriever,
	ItemLabelsRetriever,
	ItemDescriptionsRetriever,
	ItemAliasesRetriever,
	SitelinksRetriever,
	ItemUpdater,
	ItemCreator
{
	use StatementReadModelHelper;

	private array $items = [];
	private array $latestRevisionData = [];

	public function addItem( ItemWriteModel $item ): void {
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

	public function getItem( ItemId $itemId ): ?ItemWriteModel {
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

	public function getSitelinks( ItemId $itemId ): Sitelinks {
		return $this->convertToReadModel( $this->items["$itemId"] )->getSitelinks();
	}

	public function create( ItemWriteModel $item, EditMetadata $editMetadata ): ItemRevision {
		$item->setId( new ItemId( 'Q' . rand( 1, 9999 ) ) );

		return $this->update( $item, $editMetadata );
	}

	public function update( ItemWriteModel $item, EditMetadata $editMetadata ): ItemRevision {
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

	public function urlForSitelink( string $siteId, string $title ): string {
		return $this->newSiteForSiteId( $siteId )->getPageUrl( $title );
	}

	private function convertToReadModel( ItemWriteModel $item ): Item {
		return new Item(
			$item->getId(),
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

	private function newSitelinksReadModelConverter(): SitelinksReadModelConverter {
		return new SitelinksReadModelConverter( new HashSiteStore( array_map(
			[ $this, 'newSiteForSiteId' ],
			TestValidatingRequestDeserializer::ALLOWED_SITE_IDS
		) ) );
	}

	private function newSiteForSiteId( string $siteId ): Site {
		$site = new Site();
		$site->setGlobalId( $siteId );
		$site->setLinkPath( "https://$siteId.example.wiki/\$1" );

		return $site;
	}

}
