<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\LabelEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class SetItemLabel {

	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	public function __construct( ItemRetriever $itemRetriever, ItemUpdater $itemUpdater ) {
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
	}

	public function execute( SetItemLabelRequest $request ): SetItemLabelResponse {
		$itemId = new ItemId( $request->getItemId() );
		$term = new Term( $request->getLanguageCode(), $request->getLabel() );

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
