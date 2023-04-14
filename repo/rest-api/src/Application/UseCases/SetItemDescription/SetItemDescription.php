<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class SetItemDescription {

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

	public function execute( SetItemDescriptionRequest $request ): SetItemDescriptionResponse {
		$itemId = new ItemId( $request->getItemId() );
		$description = new Term( $request->getLanguageCode(), $request->getDescription() );

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
		$descriptionExists = $item->getDescriptions()->hasTermForLanguage( $request->getLanguageCode() );
		$item->getDescriptions()->setTerm( $description );

		$editSummary = $descriptionExists
			? DescriptionEditSummary::newReplaceSummary( $request->getComment(), $description )
			: DescriptionEditSummary::newAddSummary( $request->getComment(), $description );

		$revision = $this->itemUpdater->update(
			$item,
			new EditMetadata(
				$request->getEditTags(),
				$request->isBot(),
				$editSummary
			)
		);

		return new SetItemDescriptionResponse(
			$revision->getItem()->getDescriptions()[$request->getLanguageCode()],
			$revision->getLastModified(),
			$revision->getRevisionId(),
			$descriptionExists
		);
	}
}
