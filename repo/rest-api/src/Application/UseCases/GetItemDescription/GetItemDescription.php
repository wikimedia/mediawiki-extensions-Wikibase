<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemDescription;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\ItemDescriptionRetriever;

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
		$this->validator->assertValidRequest( $request );

		$itemId = new ItemId( $request->getItemId() );

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $itemId );

		$description = $this->itemDescriptionRetriever->getDescription( $itemId, $request->getLanguageCode() );
		if ( $description === null ) {
			throw new UseCaseError(
				UseCaseError::DESCRIPTION_NOT_DEFINED,
				"Item with the ID {$itemId} does not have a description in the language: {$request->getLanguageCode()}"
			);
		}

		return new GetItemDescriptionResponse(
			$description,
			$lastModified,
			$revisionId,
		);
	}
}
