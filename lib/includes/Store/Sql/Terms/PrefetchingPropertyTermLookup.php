<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Store\Sql\Terms;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;

/**
 * A {@link PrefetchingTermLookup} that only supports properties,
 * using the new, normalized schema (starting at wbt_property_terms).
 *
 * Prefetches from DatabaseTermInLangIdsResolver(DB) and stores them in $terms (current process only).
 * Looks up terms from $terms.
 *
 * Shares the same implementation with {@link PrefetchingItemTermLookup}
 *
 * @license GPL-2.0-or-later
 */
class PrefetchingPropertyTermLookup extends PrefetchingEntityTermLookupBase {

	/** @var string */
	protected $entityIdClass = NumericPropertyId::class;
	/** @var string */
	protected $statsPrefix = 'PrefetchingPropertyTermLookup';

	protected function makeMapping(): NormalizedTermStorageMapping {
		return NormalizedTermStorageMapping::factory( Property::ENTITY_TYPE );
	}
}
