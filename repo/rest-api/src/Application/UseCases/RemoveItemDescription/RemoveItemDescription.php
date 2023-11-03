<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveItemDescription;

use Wikibase\Repo\RestApi\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class RemoveItemDescription {

	private RemoveItemDescriptionValidator $useCaseValidator;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	public function __construct(
		RemoveItemDescriptionValidator $useCaseValidator,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater
	) {
		$this->useCaseValidator = $useCaseValidator;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
	}

	public function execute( RemoveItemDescriptionRequest $request ): void {
		$deserializedRequest = $this->useCaseValidator->validateAndDeserialize( $request );

		$item = $this->itemRetriever->getItem( $deserializedRequest->getItemId() );
		$description = $item->getDescriptions()->getByLanguage( $deserializedRequest->getLanguageCode() );
		$item->getDescriptions()->removeByLanguage( $deserializedRequest->getLanguageCode() );

		$providedEditMetadata = $deserializedRequest->getEditMetadata();
		$editMetadata = new EditMetadata(
			$providedEditMetadata->getTags(),
			$providedEditMetadata->isBot(),
			DescriptionEditSummary::newRemoveSummary( $providedEditMetadata->getComment(), $description )
		);
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$this->itemUpdater->update( $item, $editMetadata );
	}

}
