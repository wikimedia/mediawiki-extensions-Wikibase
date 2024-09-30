<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemLabelWithFallback;

use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemLabelWithFallback {

	private GetLatestItemRevisionMetadata $getLatestRevisionMetadata;
	private ItemLabelRetriever $itemLabelRetriever;
	private GetItemLabelWithFallbackValidator $validator;

	public function __construct(
		GetLatestItemRevisionMetadata $getLatestRevisionMetadata,
		ItemLabelRetriever $itemLabelRetriever,
		GetItemLabelWithFallbackValidator $validator
	) {
		$this->getLatestRevisionMetadata = $getLatestRevisionMetadata;
		$this->itemLabelRetriever = $itemLabelRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 * @throws ItemRedirect
	 */
	public function execute( GetItemLabelWithFallbackRequest $request ): GetItemLabelWithFallbackResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();
		$languageCode = $deserializedRequest->getLanguageCode();

		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $itemId );

		$label = $this->itemLabelRetriever->getLabel( $itemId, $languageCode );
		if ( !$label ) {
			throw UseCaseError::newResourceNotFound( 'label' );
		}

		return new GetItemLabelWithFallbackResponse(
			$label,
			$lastModified,
			$revisionId,
		);
	}
}
