<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItem;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\ItemDataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItem {

	private GetLatestItemRevisionMetadata $getLatestRevisionMetadata;
	private ItemDataRetriever $itemDataRetriever;
	private GetItemValidator $validator;

	public function __construct(
		GetLatestItemRevisionMetadata $getLatestRevisionMetadata,
		ItemDataRetriever $itemDataRetriever,
		GetItemValidator $validator
	) {
		$this->validator = $validator;
		$this->getLatestRevisionMetadata = $getLatestRevisionMetadata;
		$this->itemDataRetriever = $itemDataRetriever;
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
			$this->itemDataRetriever->getItemData( $itemId, $itemRequest->getFields() ),
			$lastModified,
			$revisionId
		);
	}

}
