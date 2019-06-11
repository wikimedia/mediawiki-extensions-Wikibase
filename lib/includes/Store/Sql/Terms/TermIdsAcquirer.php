<?php

namespace Wikibase\Lib\Store\Sql\Terms;

/**
 * Consumers acquire ids for stored terms to be used to link
 * entities to these terms.
 */
interface TermIdsAcquirer {

	/**
	 * Acquires IDs for the given terms.
	 *
	 * The acquirer guarantees that an in-parallel {@link TermIdsCleaner} will
	 * not result in deleting terms that have been acquired by this acquirer,
	 * should these two in-parallel processes happen to overlap on some
	 * existing term IDs. The mechanism of achieving this guarantee is complete
	 * under the following two conditions:
	 * - External linking to acquired IDs (e.g. using them as foreign keys in
	 *   other tables) must happen inside the $callback.
	 * - The in-parallel cleaner is called with set of IDs based on the absence
	 *   of any links to those IDs, in the same external places where the
	 *   callback links to them.
	 *
	 * @param array $termsArray array containing terms per type per language.
	 *  Example:
	 * 	[
	 *		'label' => [
	 *			'en' => 'some label',
	 *			'de' => 'another label',
	 *			...
	 *		],
	 *		'alias' => [
	 *			'en' => [ 'alias', 'another alias', ...],
	 *			'de' => 'de alias',
	 *			...
	 *		],
	 *		...
	 *  ]
	 *
	 * @param callable|null $callback Called with int[] $termIds right before
	 * attempting to restore any of those acquired IDs that might have been
	 * deleted by another process before {@link acquireTermIds()} has returned.
	 *
	 * @return array returns ids of acquired terms in the store
	 */
	public function acquireTermIds( array $termsArray, $callback = null ): array;

}
