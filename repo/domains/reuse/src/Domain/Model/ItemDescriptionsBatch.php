<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class ItemDescriptionsBatch {

	/**
	 * @param array<string, Descriptions> $itemDescriptionsBatch
	 */
	public function __construct( public readonly array $itemDescriptionsBatch ) {
	}

	public function getItemDescriptions( ItemId $itemId ): Descriptions {
		return $this->itemDescriptionsBatch[ $itemId->getSerialization() ];
	}

}
