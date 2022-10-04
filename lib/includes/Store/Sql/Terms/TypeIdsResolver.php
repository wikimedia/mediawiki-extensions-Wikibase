<?php

namespace Wikibase\Lib\Store\Sql\Terms;

/**
 * A service to turn type IDs into types,
 * the inverse of {@link TypeIdsAcquirer}.
 *
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
interface TypeIdsResolver {

	/**
	 * Resolves types for the given type IDs.
	 *
	 * @param int[] $typeIds
	 * @return string[] Array from type IDs to type names. Unknown IDs in $typeIds are omitted.
	 */
	public function resolveTypeIds( array $typeIds ): array;

}
