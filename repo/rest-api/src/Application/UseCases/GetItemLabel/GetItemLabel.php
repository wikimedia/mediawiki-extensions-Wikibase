<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemLabel {

	private GetLatestItemRevisionMetadata $getLatestRevisionMetadata;
	private ItemLabelRetriever $itemLabelRetriever;
	private GetItemLabelValidator $validator;

	public function __construct(
		GetLatestItemRevisionMetadata $getLatestRevisionMetadata,
		ItemLabelRetriever $itemLabelRetriever,
		GetItemLabelValidator $validator
	) {
		$this->getLatestRevisionMetadata = $getLatestRevisionMetadata;
		$this->itemLabelRetriever = $itemLabelRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 *
	 * @throws ItemRedirect
	 */
	public function execute( GetItemLabelRequest $request ): GetItemLabelResponse {
		$this->validator->assertValidRequest( $request );

		$itemId = new ItemId( $request->getItemId() );

		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $itemId );

		$label = $this->itemLabelRetriever->getLabel( $itemId, $request->getLanguageCode() );
		if ( !$label ) {
			throw new UseCaseError(
				UseCaseError::LABEL_NOT_DEFINED,
				"Item with the ID {$request->getItemId()} does not have a label in the language: {$request->getLanguageCode()}"
			);
		}

		return new GetItemLabelResponse(
			$label,
			$lastModified,
			$revisionId,
		);
	}
}
