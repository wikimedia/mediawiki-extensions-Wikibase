<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabel;

use Wikibase\Repo\Domains\Crud\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ItemRedirect;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemLabelRetriever;

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
	 * @throws ItemRedirect
	 */
	public function execute( GetItemLabelRequest $request ): GetItemLabelResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();
		$languageCode = $deserializedRequest->getLanguageCode();

		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $itemId );

		$label = $this->itemLabelRetriever->getLabel( $itemId, $languageCode );
		if ( !$label ) {
			throw UseCaseError::newResourceNotFound( 'label' );
		}

		return new GetItemLabelResponse(
			$label,
			$lastModified,
			$revisionId,
		);
	}
}
