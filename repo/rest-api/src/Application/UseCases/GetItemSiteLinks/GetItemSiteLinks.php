<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLinks;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Domain\Services\SiteLinksRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemSiteLinks {

	private GetLatestItemRevisionMetadata $getLatestRevisionMetadata;
	private SiteLinksRetriever $siteLinksRetriever;

	public function __construct(
		GetLatestItemRevisionMetadata $getLatestRevisionMetadata,
		SiteLinksRetriever $siteLinksRetriever
	) {
		$this->getLatestRevisionMetadata = $getLatestRevisionMetadata;
		$this->siteLinksRetriever = $siteLinksRetriever;
	}

	public function execute( GetItemSiteLinksRequest $request ): GetItemSiteLinksResponse {
		$itemId = new ItemId( $request->getItemId() );

		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $itemId );

		return new GetItemSiteLinksResponse(
			$this->siteLinksRetriever->getSiteLinks( $itemId ),
			$lastModified,
			$revisionId
		);
	}

}
