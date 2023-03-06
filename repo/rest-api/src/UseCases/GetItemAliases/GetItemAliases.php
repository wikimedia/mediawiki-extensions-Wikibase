<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemAliases;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Services\ItemAliasesRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;

/**
 * @license GPL-2.0-or-later
 */
class GetItemAliases {

	private ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever;
	private ItemAliasesRetriever $itemAliasesRetriever;
	private GetItemAliasesValidator $validator;

	public function __construct(
		ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever,
		ItemAliasesRetriever $itemAliasesRetriever,
		GetItemAliasesValidator $validator
	) {
		$this->itemRevisionMetadataRetriever = $itemRevisionMetadataRetriever;
		$this->itemAliasesRetriever = $itemAliasesRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseException
	 */
	public function execute( GetItemAliasesRequest $request ): GetItemAliasesResponse {
		$this->validator->assertValidRequest( $request );

		$itemId = new ItemId( $request->getItemId() );

		$metaDataResult = $this->itemRevisionMetadataRetriever->getLatestRevisionMetadata( $itemId );

		return new GetItemAliasesResponse(
			$this->itemAliasesRetriever->getAliases( $itemId ),
			$metaDataResult->getRevisionTimestamp(),
			$metaDataResult->getRevisionId(),
		);
	}

}
