<?php

namespace Wikibase\Lib\Store\Sql\Terms;

/**
 * A service to turn term in lang IDs into terms,
 * the inverse of {@link TermInLangIdsAcquirer}.
 *
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
interface TermInLangIdsResolver {

	/**
	 * Resolves terms for the given term in lang IDs.
	 *
	 * Note that the information whether the leaf nodes were single strings or arrays of strings
	 * is lost: while {@link TermInLangIdsAcquirer::acquireTermInLangIds} accepts both, this method always
	 * returns arrays of strings.
	 *
	 * @param int[] $termInLangIds
	 * @param null|string[] $types If not null, only include results of these types
	 * @param null|string[] $languages If not null, only include results in these languages
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
	public function resolveTermInLangIds(
		array $termInLangIds,
		array $types = null,
		array $languages = null
	): array;

	/**
	 * Resolves terms for the given groups of term in lang IDs.
	 *
	 * The input is an array of term in lang ID arrays, with arbitrary keys.
	 * The return value is an array of terms structures, with the same keys,
	 * where the values belong to the term in lang IDs corresponding to that key.
	 * One call to this method is effectively equivalent to multiple calls to
	 * {@link resolveTermInLangIds} with the individual term in lang ID arrays, but may be
	 * more efficient than that, e.g. resolving all the term in lang IDs in one batch
	 * and then grouping them correctly afterwards.
	 *
	 * @param int[][] $groupedTermInLangIds
	 * @param null|string[] $types If not null, only include results of these types
	 * @param null|string[] $languages If not null, only include results in these languages
	 * @return array[]
	 */
	public function resolveGroupedTermInLangIds(
		array $groupedTermInLangIds,
		array $types = null,
		array $languages = null
	): array;

}
