<?php

namespace Wikibase\Lib\Store\Sql\MediaWikiTermStore;

/**
 * Accessor to the entity-type agnostic normalized term store.
 *
 * Consumers can use per-storage-type implementations of this
 * accessor to acquire ids for stored terms to be used to link
 * entities to these terms.
 */
interface NormalizedTermStoreSchemaAccess {

	/**
	 * acquires term_in_lang ids for given terms, inserting non-existing ones.
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
	public function acquireTermIds( array $termsArray ) : array;

}
