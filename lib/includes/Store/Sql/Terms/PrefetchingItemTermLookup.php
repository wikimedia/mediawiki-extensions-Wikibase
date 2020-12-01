<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Store\Sql\Terms;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;

/**
 * A {@link PrefetchingTermLookup} that only supports items,
 * using the new, normalized schema (starting at wbt_item_terms).
 *
 * Prefetches from DatabaseTermInLangIdsResolver(DB) and stores them in $terms (current process only).
 * Looks up terms from $terms.
 *
 * Shares the same implementation with {@link PrefetchingPropertyTermLookup}
 *
 * @license GPL-2.0-or-later
 */
class PrefetchingItemTermLookup extends PrefetchingEntityTermLookupBase {

	/** @var string */
	protected $entityIdClass = ItemId::class;
	/** @var string */
	protected $statsPrefix = 'PrefetchingItemTermLookup';

	protected function makeMapping(): NormalizedTermStorageMapping {
		return NormalizedTermStorageMapping::factory( Item::ENTITY_TYPE );
	}
}
