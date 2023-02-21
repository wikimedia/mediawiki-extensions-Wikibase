<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemDescriptions;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Services\ItemDescriptionsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\ItemRedirectException;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;

/**
 * @license GPL-2.0-or-later
 */
class GetItemDescriptions {

	private ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever;
	private ItemDescriptionsRetriever $itemDescriptionsRetriever;
	private GetItemDescriptionsValidator $validator;

	public function __construct(
		ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever,
		ItemDescriptionsRetriever $itemDescriptionsRetriever,
		GetItemDescriptionsValidator $validator
	) {
		$this->itemRevisionMetadataRetriever = $itemRevisionMetadataRetriever;
		$this->itemDescriptionsRetriever = $itemDescriptionsRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseException|ItemRedirectException
	 */
	public function execute( GetItemDescriptionsRequest $request ): GetItemDescriptionsResponse {
		$this->validator->assertValidRequest( $request );

		$itemId = new ItemId( $request->getItemId() );

		$metaDataResult = $this->itemRevisionMetadataRetriever->getLatestRevisionMetadata( $itemId );

		if ( !$metaDataResult->itemExists() ) {
			throw new UseCaseException(
				ErrorResponse::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$request->getItemId()}"
			);
		}

		if ( $metaDataResult->isRedirect() ) {
			throw new ItemRedirectException(
				$metaDataResult->getRedirectTarget()->getSerialization()
			);
		}

		return new GetItemDescriptionsResponse(
			$this->itemDescriptionsRetriever->getDescriptions( $itemId ),
			$metaDataResult->getRevisionTimestamp(),
			$metaDataResult->getRevisionId(),
		);
	}
}
