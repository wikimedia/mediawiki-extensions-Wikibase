<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveItemSiteLink;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\SiteLinkEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class RemoveItemSiteLink {

	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private AssertItemExists $assertItemExists;

	public function __construct( ItemRetriever $itemRetriever, ItemUpdater $itemUpdater, AssertItemExists $assertItemExists ) {
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->assertItemExists = $assertItemExists;
	}

	/**
	 * @throws ItemRedirect if the item is a redirect
	 * @throws UseCaseError if the item does not exist
	 */
	public function execute( RemoveItemSiteLinkRequest $request ): void {
		$itemId = new ItemId( $request->getItemId() );
		$siteId = $request->getSiteId();

		$this->assertItemExists->execute( $itemId );
		$item = $this->itemRetriever->getItem( $itemId );

		if ( !$item->hasLinkToSite( $siteId ) ) {
			throw new UseCaseError(
				UseCaseError::SITELINK_NOT_DEFINED,
				"No sitelink found for the ID: $itemId for the site $siteId"
			);
		}

		$item->removeSiteLink( $siteId );
		$this->itemUpdater->update(
			$item, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata( $request->getEditTags(), $request->isBot(), new SiteLinkEditSummary() )
		);
	}

}
