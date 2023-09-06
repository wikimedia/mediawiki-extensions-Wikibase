<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions;

use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\ItemDescriptionsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemDescriptions {

	private GetLatestItemRevisionMetadata $getLatestRevisionMetadata;
	private ItemDescriptionsRetriever $itemDescriptionsRetriever;
	private GetItemDescriptionsValidator $validator;

	public function __construct(
		GetLatestItemRevisionMetadata $getLatestRevisionMetadata,
		ItemDescriptionsRetriever $itemDescriptionsRetriever,
		GetItemDescriptionsValidator $validator
	) {
		$this->getLatestRevisionMetadata = $getLatestRevisionMetadata;
		$this->itemDescriptionsRetriever = $itemDescriptionsRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 * @throws ItemRedirect
	 */
	public function execute( GetItemDescriptionsRequest $request ): GetItemDescriptionsResponse {
		$itemId = $this->validator->validateAndDeserialize( $request )->getItemId();

		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $itemId );

		return new GetItemDescriptionsResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Item validated and exists
			$this->itemDescriptionsRetriever->getDescriptions( $itemId ),
			$lastModified,
			$revisionId,
		);
	}
}
