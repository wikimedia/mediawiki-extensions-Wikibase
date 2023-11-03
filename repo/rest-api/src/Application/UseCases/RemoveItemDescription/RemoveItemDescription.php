<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveItemDescription;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class RemoveItemDescription {

	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	public function __construct( ItemRetriever $itemRetriever, ItemUpdater $itemUpdater ) {
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
	}

	public function execute( RemoveItemDescriptionRequest $request ): void {
		$itemId = new ItemId( $request->getItemId() );

		$item = $this->itemRetriever->getItem( $itemId );
		$description = $item->getDescriptions()->getByLanguage( $request->getLanguageCode() );
		$item->getDescriptions()->removeByLanguage( $request->getLanguageCode() );

		$summary = DescriptionEditSummary::newRemoveSummary( $request->getComment(), $description );
		$editMetadata = new EditMetadata( $request->getEditTags(), $request->isBot(), $summary );
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$this->itemUpdater->update( $item, $editMetadata );
	}

}
