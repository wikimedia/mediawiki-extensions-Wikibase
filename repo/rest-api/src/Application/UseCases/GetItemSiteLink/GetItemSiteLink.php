<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLink;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Domain\Services\SiteLinkRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemSiteLink {

	private GetLatestItemRevisionMetadata $getRevisionMetadata;
	private SiteLinkRetriever $siteLinkRetriever;

	public function __construct( GetLatestItemRevisionMetadata $getRevisionMetadata, SiteLinkRetriever $siteLinkRetriever ) {
		$this->getRevisionMetadata = $getRevisionMetadata;
		$this->siteLinkRetriever = $siteLinkRetriever;
	}

	public function execute( GetItemSiteLinkRequest $request ): GetItemSiteLinkResponse {
		$itemId = new ItemId( $request->getItemId() );

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $itemId );

		return new GetItemSiteLinkResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$this->siteLinkRetriever->getSiteLink( $itemId, $request->getSiteId() ),
			$lastModified,
			$revisionId
		);
	}
}
