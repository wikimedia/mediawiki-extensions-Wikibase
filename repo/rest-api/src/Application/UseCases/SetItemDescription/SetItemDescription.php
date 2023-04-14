<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @license GPL-2.0-or-later
 */
class SetItemDescription {

	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	public function __construct( ItemRetriever $itemRetriever, ItemUpdater $itemUpdater ) {
		$this->itemRetriever = $itemRetriever;
		$this->itemUpdater = $itemUpdater;
	}

	public function execute( SetItemDescriptionRequest $request ): SetItemDescriptionResponse {
		$item = $this->itemRetriever->getItem( new ItemId( $request->getItemId() ) );
		$description = new Term( $request->getLanguageCode(), $request->getDescription() );
		$item->getDescriptions()->setTerm( $description );

		$revision = $this->itemUpdater->update(
			$item,
			new EditMetadata(
				$request->getEditTags(),
				$request->isBot(),
				new DescriptionEditSummary( $description, $request->getComment() )
			)
		);

		return new SetItemDescriptionResponse(
			$revision->getItem()->getDescriptions()[$request->getLanguageCode()],
			$revision->getLastModified(),
			$revision->getRevisionId()
		);
	}
}
