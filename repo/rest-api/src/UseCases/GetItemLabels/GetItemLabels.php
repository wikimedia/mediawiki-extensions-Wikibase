<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemLabels;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\ItemRedirectException;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;

/**
 * @license GPL-2.0-or-later
 */
class GetItemLabels {

	private ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever;
	private ItemLabelsRetriever $itemLabelsRetriever;
	private GetItemLabelsValidator $validator;

	public function __construct(
		ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever,
		ItemLabelsRetriever $itemLabelsRetriever,
		GetItemLabelsValidator $validator
	) {
		$this->itemRevisionMetadataRetriever = $itemRevisionMetadataRetriever;
		$this->itemLabelsRetriever = $itemLabelsRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseException
	 * @throws ItemRedirectException
	 */
	public function execute( GetItemLabelsRequest $request ): GetItemLabelsResponse {
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

		return new GetItemLabelsResponse(
			$this->itemLabelsRetriever->getLabels( $itemId ),
			$metaDataResult->getRevisionTimestamp(),
			$metaDataResult->getRevisionId(),
		);
	}
}
