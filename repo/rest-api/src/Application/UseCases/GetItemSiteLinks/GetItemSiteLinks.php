<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLinks;

use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\SiteLinksRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemSiteLinks {

	private GetItemSiteLinksValidator $validator;
	private GetLatestItemRevisionMetadata $getLatestRevisionMetadata;
	private SiteLinksRetriever $siteLinksRetriever;

	public function __construct(
		GetItemSiteLinksValidator $validator,
		GetLatestItemRevisionMetadata $getLatestRevisionMetadata,
		SiteLinksRetriever $siteLinksRetriever
	) {
		$this->validator = $validator;
		$this->getLatestRevisionMetadata = $getLatestRevisionMetadata;
		$this->siteLinksRetriever = $siteLinksRetriever;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetItemSiteLinksRequest $request ): GetItemSiteLinksResponse {
		$itemId = $this->validator->validateAndDeserialize( $request )->getItemId();

		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $itemId );

		return new GetItemSiteLinksResponse(
			$this->siteLinksRetriever->getSiteLinks( $itemId ),
			$lastModified,
			$revisionId
		);
	}

}
