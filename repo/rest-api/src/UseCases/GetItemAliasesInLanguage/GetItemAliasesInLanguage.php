<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemAliasesInLanguage;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Services\ItemAliasesInLanguageRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemAliasesInLanguage {

	private ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever;
	private ItemAliasesInLanguageRetriever $itemAliasesInLanguageRetriever;

	public function __construct(
		ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever,
		ItemAliasesInLanguageRetriever $itemAliasesInLanguageRetriever
	) {
		$this->itemRevisionMetadataRetriever = $itemRevisionMetadataRetriever;
		$this->itemAliasesInLanguageRetriever = $itemAliasesInLanguageRetriever;
	}

	public function execute( GetItemAliasesInLanguageRequest $request ): GetItemAliasesInLanguageResponse {
		$itemId = new ItemId( $request->getItemId() );

		$metaDataResult = $this->itemRevisionMetadataRetriever->getLatestRevisionMetadata( $itemId );

		return new GetItemAliasesInLanguageResponse(
			$this->itemAliasesInLanguageRetriever->getAliasesInLanguage( $itemId, $request->getLanguageCode() ),
			$metaDataResult->getRevisionTimestamp(),
			$metaDataResult->getRevisionId(),
		);
	}
}
