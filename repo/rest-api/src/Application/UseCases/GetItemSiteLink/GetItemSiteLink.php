<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLink;

use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
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
	 */
	public function execute( GetItemSiteLinkRequest $request ): GetItemSiteLinkResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $itemId );

		return new GetItemSiteLinkResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$this->siteLinkRetriever->getSiteLink( $itemId, $deserializedRequest->getSiteId() ),
			$lastModified,
			$revisionId
		);
	}
}
