<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemLabels {

	private GetLatestItemRevisionMetadata $getLatestRevisionMetadata;
	private ItemLabelsRetriever $itemLabelsRetriever;
	private GetItemLabelsValidator $validator;

	public function __construct(
		GetLatestItemRevisionMetadata $getLatestRevisionMetadata,
		ItemLabelsRetriever $itemLabelsRetriever,
		GetItemLabelsValidator $validator
	) {
		$this->getLatestRevisionMetadata = $getLatestRevisionMetadata;
		$this->itemLabelsRetriever = $itemLabelsRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 * @throws ItemRedirect
	 */
	public function execute( GetItemLabelsRequest $request ): GetItemLabelsResponse {
		$this->validator->assertValidRequest( $request );

		$itemId = new ItemId( $request->getItemId() );

		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $itemId );

		return new GetItemLabelsResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Item validated and exists
			$this->itemLabelsRetriever->getLabels( $itemId ),
			$lastModified,
			$revisionId,
		);
	}
}
