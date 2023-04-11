<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\LabelEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class SetItemLabel {

	private ItemRevisionMetadataRetriever $metadataRetriever;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	public function __construct(
		ItemRevisionMetadataRetriever $metadataRetriever,
		ItemRetriever $itemRetriever,
		ItemUpdater $itemUpdater
	) {
		$this->metadataRetriever = $metadataRetriever;
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
	}

	public function execute( SetItemLabelRequest $request ): SetItemLabelResponse {
		$itemId = new ItemId( $request->getItemId() );
		$term = new Term( $request->getLanguageCode(), $request->getLabel() );

		$latestRevision = $this->metadataRetriever->getLatestRevisionMetadata( $itemId );
		if ( $latestRevision->isRedirect() ) {
			throw new UseCaseError(
				UseCaseError::ITEM_REDIRECTED,
				"Item {$request->getItemId()} has been merged into {$latestRevision->getRedirectTarget()}."
			);
		} elseif ( !$latestRevision->itemExists() ) {
			throw new UseCaseError(
				UseCaseError::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$request->getItemId()}"
			);
		}

		$item = $this->itemRetriever->getItem( $itemId );
		$labelExists = $item->getLabels()->hasTermForLanguage( $request->getLanguageCode() );
		$item->getLabels()->setTerm( $term );

		$editSummary = $labelExists
			? LabelEditSummary::newReplaceSummary( $request->getComment(), $term )
			: LabelEditSummary::newAddSummary( $request->getComment(), $term );

		$editMetadata = new EditMetadata( $request->getEditTags(), $request->isBot(), $editSummary );
		$newRevision = $this->itemUpdater->update( $item, $editMetadata );

		return new SetItemLabelResponse(
			$newRevision->getItem()->getLabels()[$request->getLanguageCode()],
			$newRevision->getLastModified(),
			$newRevision->getRevisionId(),
			$labelExists
		);
	}

}
