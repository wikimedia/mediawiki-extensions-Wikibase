<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class Item {

	public function __construct(
		public readonly ItemId $id,
		public readonly Labels $labels,
		public readonly Descriptions $descriptions,
		public readonly Aliases $aliases,
	) {
	}

}
