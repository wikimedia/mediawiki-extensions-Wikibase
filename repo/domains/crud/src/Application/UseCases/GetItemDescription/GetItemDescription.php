<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescription;

use Wikibase\Repo\Domains\Crud\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ItemRedirect;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemDescriptionRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemDescription {

	private GetLatestItemRevisionMetadata $getRevisionMetadata;
	private ItemDescriptionRetriever $itemDescriptionRetriever;
	private GetItemDescriptionValidator $validator;

	public function __construct(
		GetLatestItemRevisionMetadata $getRevisionMetadata,
		ItemDescriptionRetriever $itemDescriptionRetriever,
		GetItemDescriptionValidator $validator
	) {
		$this->getRevisionMetadata = $getRevisionMetadata;
		$this->itemDescriptionRetriever = $itemDescriptionRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 * @throws ItemRedirect
	 */
	public function execute( GetItemDescriptionRequest $request ): GetItemDescriptionResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();
		$languageCode = $deserializedRequest->getLanguageCode();

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $itemId );

		$description = $this->itemDescriptionRetriever->getDescription( $itemId, $languageCode );
		if ( $description === null ) {
			throw UseCaseError::newResourceNotFound( 'description' );
		}

		return new GetItemDescriptionResponse(
			$description,
			$lastModified,
			$revisionId,
		);
	}
}
