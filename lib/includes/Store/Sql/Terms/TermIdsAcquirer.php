<?php

namespace Wikibase\Lib\Store\Sql\Terms;

/**
 * Consumers acquire ids for stored terms to be used to link
 * entities to these terms.
 */
interface TermIdsAcquirer {

	/**
	 * acquires ids for given terms.
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
	 * @return array returns ids of acquired terms in the store
	 */
	public function acquireTermIds( array $termsArray ): array;

}
