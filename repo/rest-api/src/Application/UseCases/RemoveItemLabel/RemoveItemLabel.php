<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveItemLabel;

use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\LabelEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class RemoveItemLabel {

	private RemoveItemLabelValidator $useCaseValidator;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	public function __construct(
		RemoveItemLabelValidator $useCaseValidator,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater
	) {
		$this->useCaseValidator = $useCaseValidator;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
	}

	public function execute( RemoveItemLabelRequest $request ): void {
		$deserializedRequest = $this->useCaseValidator->validateAndDeserialize( $request );

		$item = $this->itemRetriever->getItem( $deserializedRequest->getItemId() );
		$label = $item->getLabels()->getByLanguage( $deserializedRequest->getLanguageCode() );
		$item->getLabels()->removeByLanguage( $deserializedRequest->getLanguageCode() );

		$providedEditMetadata = $deserializedRequest->getEditMetadata();
		$summary = LabelEditSummary::newRemoveSummary( $providedEditMetadata->getComment(), $label );

		$this->itemUpdater->update(
			$item, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata( $providedEditMetadata->getTags(), $providedEditMetadata->isBot(), $summary )
		);
	}

}
