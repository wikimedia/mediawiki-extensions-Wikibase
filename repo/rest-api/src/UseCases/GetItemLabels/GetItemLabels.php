<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemLabels;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\ItemRedirectResponse;

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
	 * @return GetItemLabelsErrorResponse|GetItemLabelsSuccessResponse|ItemRedirectResponse
	 */
	public function execute( GetItemLabelsRequest $request ) {
		$validationError = $this->validator->validate( $request );
		if ( $validationError ) {
			return GetItemLabelsErrorResponse::newFromValidationError( $validationError );
		}

		$itemId = new ItemId( $request->getItemId() );

		$metaDataResult = $this->itemRevisionMetadataRetriever->getLatestRevisionMetadata( $itemId );

		if ( !$metaDataResult->itemExists() ) {
			return new GetItemLabelsErrorResponse(
				ErrorResponse::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$request->getItemId()}"
			);
		} elseif ( $metaDataResult->isRedirect() ) {
			return new ItemRedirectResponse( $metaDataResult->getRedirectTarget()->getSerialization() );
		}

		return new GetItemLabelsSuccessResponse(
			$this->itemLabelsRetriever->getLabels( $itemId ),
			$metaDataResult->getRevisionTimestamp(),
			$metaDataResult->getRevisionId(),
		);
	}
}
