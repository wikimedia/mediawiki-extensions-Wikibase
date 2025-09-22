<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class ItemLabelsBatch {

	/**
	 * @param array<string, Labels> $itemLabelsBatch
	 */
	public function __construct( public readonly array $itemLabelsBatch ) {
	}

	public function getItemLabels( ItemId $itemId ): Labels {
		return $this->itemLabelsBatch[ $itemId->getSerialization() ];
	}

}
