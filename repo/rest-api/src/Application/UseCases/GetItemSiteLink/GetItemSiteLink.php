<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLink;

use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\SiteLinkRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemSiteLink {

	private GetItemSiteLinkValidator $validator;
	private GetLatestItemRevisionMetadata $getRevisionMetadata;
	private SiteLinkRetriever $siteLinkRetriever;

	public function __construct(
		GetItemSiteLinkValidator $validator,
		GetLatestItemRevisionMetadata $getRevisionMetadata,
		SiteLinkRetriever $siteLinkRetriever
	) {
		$this->validator = $validator;
		$this->getRevisionMetadata = $getRevisionMetadata;
		$this->siteLinkRetriever = $siteLinkRetriever;
	}

	/**
	 * @throws UseCaseError
	 * @throws ItemRedirect
	 */
	public function execute( GetItemSiteLinkRequest $request ): GetItemSiteLinkResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();
		$siteId = $deserializedRequest->getSiteId();

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $itemId );

		$siteLink = $this->siteLinkRetriever->getSiteLink( $itemId, $siteId );
		if ( !$siteLink ) {
			throw new UseCaseError(
				UseCaseError::SITELINK_NOT_DEFINED,
				"No sitelink found for the ID: {$itemId->getSerialization()} for the site $siteId"
			);
		}

		return new GetItemSiteLinkResponse( $siteLink, $lastModified, $revisionId );
	}
}
