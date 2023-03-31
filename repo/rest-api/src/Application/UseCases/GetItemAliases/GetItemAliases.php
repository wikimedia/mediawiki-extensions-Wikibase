<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\ItemAliasesRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;

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
	 * @throws UseCaseError
	 *
	 * @throws ItemRedirect
	 */
	public function execute( GetItemAliasesRequest $request ): GetItemAliasesResponse {
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

		return new GetItemAliasesResponse(
			$this->itemAliasesRetriever->getAliases( $itemId ),
			$metaDataResult->getRevisionTimestamp(),
			$metaDataResult->getRevisionId(),
		);
	}

}
