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

	/**
	 * Resolves terms for the given batches of term IDs.
	 *
	 * The input is an array of term ID arrays, with arbitrary keys.
	 * The return value is an array of terms structures, with the same keys,
	 * where the values belong to the term IDs corresponding to that key.
	 * One call to this method is effectively equivalent to multiple calls to
	 * {@link resolveTermIds} with the individual term ID arrays, but may be
	 * more efficient than that.
	 *
	 * @param int[][] $termIdsBatches
	 * @return array[]
	 */
	public function resolveTermIdsBatches( array $termIdsBatches ): array;

}
