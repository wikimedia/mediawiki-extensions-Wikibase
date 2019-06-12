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
	 * @param null|string[] $types Only include results of these types
	 * @param null|string[] $languages Only include results in these languages
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
	public function resolveTermIds(
		array $termIds,
		array $types = null,
		array $languages = null
	): array;

	/**
	 * Resolves terms for the given groups of term IDs.
	 *
	 * The input is an array of term ID arrays, with arbitrary keys.
	 * The return value is an array of terms structures, with the same keys,
	 * where the values belong to the term IDs corresponding to that key.
	 * One call to this method is effectively equivalent to multiple calls to
	 * {@link resolveTermIds} with the individual term ID arrays, but may be
	 * more efficient than that, e. g. resolving all the term IDs in one batch
	 * and then grouping them correctly afterwards.
	 *
	 * @param int[][] $groupedTermIds
	 * @param null|string[] $types Only include results of these types
	 * @param null|string[] $languages Only include results in these languages
	 * @return array[]
	 */
	public function resolveGroupedTermIds(
		array $groupedTermIds,
		array $types = null,
		array $languages = null
	): array;

}
