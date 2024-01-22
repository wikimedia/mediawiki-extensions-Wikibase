<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveItemSiteLink;

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
	private RemoveItemSiteLinkValidator $validator;

	public function __construct(
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater,
		AssertItemExists $assertItemExists,
		RemoveItemSiteLinkValidator $validator
	) {
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
		$this->assertItemExists = $assertItemExists;
		$this->validator = $validator;
	}

	/**
	 * @throws ItemRedirect
	 * @throws UseCaseError
	 */
	public function execute( RemoveItemSiteLinkRequest $request ): void {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();
		$siteId = $deserializedRequest->getSiteId();
		$editMetadata = $deserializedRequest->getEditMetadata();

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
			new EditMetadata( $editMetadata->getTags(), $editMetadata->isBot(), new SiteLinkEditSummary() )
		);
	}

}
