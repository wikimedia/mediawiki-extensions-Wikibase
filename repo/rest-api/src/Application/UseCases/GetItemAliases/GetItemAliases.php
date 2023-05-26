<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\ItemAliasesRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemAliases {

	private GetLatestItemRevisionMetadata $getLatestRevisionMetadata;
	private ItemAliasesRetriever $itemAliasesRetriever;
	private GetItemAliasesValidator $validator;

	public function __construct(
		GetLatestItemRevisionMetadata $getLatestRevisionMetadata,
		ItemAliasesRetriever $itemAliasesRetriever,
		GetItemAliasesValidator $validator
	) {
		$this->getLatestRevisionMetadata = $getLatestRevisionMetadata;
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

		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $itemId );

		return new GetItemAliasesResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Item validated and exists
			$this->itemAliasesRetriever->getAliases( $itemId ),
			$lastModified,
			$revisionId,
		);
	}

}
