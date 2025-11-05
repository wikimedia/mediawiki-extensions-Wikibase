<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class ItemsBatch {

	/**
	 * @param (Item|null)[] $itemsById
	 */
	public function __construct( private readonly array $itemsById ) {
	}

	/**
	 * @param ItemId $id
	 * @return Item|null - the item, or null if a requested item does not exist
	 */
	public function getItem( ItemId $id ): ?Item {
		if ( !array_key_exists( $id->getSerialization(), $this->itemsById ) ) {
			throw new InvalidArgumentException( "Item ID '$id' is not part of the batch because it was not requested." );
		}

		return $this->itemsById[$id->getSerialization()];
	}

}
