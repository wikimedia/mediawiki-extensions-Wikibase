<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemAliasesInLanguage;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Services\ItemAliasesInLanguageRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class GetItemAliasesInLanguage {

	private ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever;
	private ItemAliasesInLanguageRetriever $itemAliasesInLanguageRetriever;
	private GetItemAliasesInLanguageValidator $validator;

	public function __construct(
		ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever,
		ItemAliasesInLanguageRetriever $itemAliasesInLanguageRetriever,
		GetItemAliasesInLanguageValidator $validator
	) {
		$this->itemRevisionMetadataRetriever = $itemRevisionMetadataRetriever;
		$this->itemAliasesInLanguageRetriever = $itemAliasesInLanguageRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 *
	 * @throws ItemRedirect
	 */
	public function execute( GetItemAliasesInLanguageRequest $request ): GetItemAliasesInLanguageResponse {
		$this->validator->assertValidRequest( $request );

		$itemId = new ItemId( $request->getItemId() );

		$metaDataResult = $this->itemRevisionMetadataRetriever->getLatestRevisionMetadata( $itemId );

		if ( !$metaDataResult->itemExists() ) {
			throw new UseCaseError(
				UseCaseError::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$request->getItemId()}"
			);
		}

		if ( $metaDataResult->isRedirect() ) {
			throw new ItemRedirect(
				$metaDataResult->getRedirectTarget()->getSerialization()
			);
		}

		$aliases = $this->itemAliasesInLanguageRetriever->getAliasesInLanguage( $itemId, $request->getLanguageCode() );

		if ( !$aliases ) {
			throw new UseCaseError(
				UseCaseError::ALIAS_NOT_DEFINED,
				"Item with the ID {$request->getItemId()} does not have an alias in the language: {$request->getLanguageCode()}"
			);
		}

		return new GetItemAliasesInLanguageResponse(
			$aliases,
			$metaDataResult->getRevisionTimestamp(),
			$metaDataResult->getRevisionId(),
		);
	}
}
