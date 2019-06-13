<?php

namespace Wikibase\Lib\Store\Sql\Terms;

/**
 * Lookup service to fetch ids of stored types.
 */
interface TypeIdsLookup {

	/**
	 * Lookup type ids for given type names.
	 *
	 * @param string[] $types
	 * @return int[] array type names to type ids
	 */
	public function lookupTypeIds( array $types ): array;
}
