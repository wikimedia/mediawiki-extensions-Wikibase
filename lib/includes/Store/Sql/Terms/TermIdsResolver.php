<?php

namespace Wikibase\Lib\Store\Sql\Terms;

/**
 * A service to turn term IDs into terms,
 * the inverse of {@link TermIdsAcquirer}.
 */
interface TermIdsResolver {

	/**
	 * Resolves terms for the given term IDs.
	 *
	 * Note that the information whether the leaf nodes were single strings or arrays of strings
	 * is lost: while {@link TermIdsAcquirer::acquireTermIds} accepts both, this method always
	 * returns arrays of strings.
	 *
	 * @param int[] $termIds
	 * @return array containing terms per type per language.
	 *  Example:
	 * 	[
	 *		'label' => [
	 *			'en' => [ 'some label' ],
	 *			'de' => [ 'another label' ],
	 *			...
	 *		],
	 *		'alias' => [
	 *			'en' => [ 'alias', 'another alias', ...],
	 *			'de' => [ 'de alias' ],
	 *			...
	 *		],
	 *		...
	 *  ]
	 */
	public function resolveTermIds( array $termIds ): array;

}
