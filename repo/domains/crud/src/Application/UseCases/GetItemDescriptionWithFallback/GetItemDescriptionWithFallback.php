<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescriptionWithFallback;

use Wikibase\Repo\Domains\Crud\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ItemRedirect;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemDescriptionWithFallbackRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemDescriptionWithFallback {

	private GetItemDescriptionWithFallbackValidator $validator;
	private GetLatestItemRevisionMetadata $getRevisionMetadata;
	private ItemDescriptionWithFallbackRetriever $itemDescriptionRetriever;

	public function __construct(
		GetItemDescriptionWithFallbackValidator $validator,
		GetLatestItemRevisionMetadata $getRevisionMetadata,
		ItemDescriptionWithFallbackRetriever $itemDescriptionRetriever
	) {
		$this->validator = $validator;
		$this->getRevisionMetadata = $getRevisionMetadata;
		$this->itemDescriptionRetriever = $itemDescriptionRetriever;
	}

	/**
	 * @throws UseCaseError
	 * @throws ItemRedirect
	 */
	public function execute( GetItemDescriptionWithFallbackRequest $request ): GetItemDescriptionWithFallbackResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$itemId = $deserializedRequest->getItemId();
		$languageCode = $deserializedRequest->getLanguageCode();

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $itemId );

		$description = $this->itemDescriptionRetriever->getDescription( $itemId, $languageCode );
		if ( $description === null ) {
			throw UseCaseError::newResourceNotFound( 'description' );
		}

		return new GetItemDescriptionWithFallbackResponse( $description, $lastModified, $revisionId, );
	}
}
