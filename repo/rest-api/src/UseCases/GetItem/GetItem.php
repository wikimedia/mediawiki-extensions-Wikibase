<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Serializers\ItemSerializer;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItem {

	private $itemRetriever;
	private $itemSerializer;

	public function __construct( ItemRetriever $itemRetriever, ItemSerializer $itemSerializer ) {
		$this->itemRetriever = $itemRetriever;
		$this->itemSerializer = $itemSerializer;
	}

	public function execute( GetItemRequest $itemRequest ): GetItemResult {
		$itemId = new ItemId( $itemRequest->getItemId() );
		$item = $this->itemRetriever->getItem( $itemId );
		return new GetItemResult( $this->itemSerializer->serialize( $item ) );
	}
}
