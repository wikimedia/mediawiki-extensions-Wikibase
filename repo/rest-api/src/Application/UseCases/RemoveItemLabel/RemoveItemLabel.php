<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveItemLabel;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\LabelEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class RemoveItemLabel {

	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	public function __construct( ItemRetriever $itemRetriever, ItemUpdater $itemUpdater ) {
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
	}

	public function execute( RemoveItemLabelRequest $request ): void {
		$itemId = new ItemId( $request->getItemId() );

		$item = $this->itemRetriever->getItem( $itemId );
		$label = $item->getLabels()->getByLanguage( $request->getLanguageCode() );
		$item->getLabels()->removeByLanguage( $request->getLanguageCode() );

		$summary = LabelEditSummary::newRemoveSummary( $request->getComment(), $label );

		$this->itemUpdater->update(
			$item, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata( $request->getEditTags(), $request->isBot(), $summary )
		);
	}

}
