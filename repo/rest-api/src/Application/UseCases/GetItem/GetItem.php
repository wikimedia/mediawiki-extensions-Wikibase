<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItem;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\ItemPartsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItem {

	private GetLatestItemRevisionMetadata $getLatestRevisionMetadata;
	private ItemPartsRetriever $itemPartsRetriever;
	private GetItemValidator $validator;

	public function __construct(
		GetLatestItemRevisionMetadata $getLatestRevisionMetadata,
		ItemPartsRetriever $itemPartsRetriever,
		GetItemValidator $validator
	) {
		$this->validator = $validator;
		$this->getLatestRevisionMetadata = $getLatestRevisionMetadata;
		$this->itemPartsRetriever = $itemPartsRetriever;
	}

	/**
	 * @throws UseCaseError
	 * @throws ItemRedirect
	 */
	public function execute( GetItemRequest $itemRequest ): GetItemResponse {
		$this->validator->assertValidRequest( $itemRequest );

		$itemId = new ItemId( $itemRequest->getItemId() );
		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $itemId );

		return new GetItemResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Item validated and exists
			$this->itemPartsRetriever->getItemParts( $itemId, $itemRequest->getFields() ),
			$lastModified,
			$revisionId
		);
	}

}
