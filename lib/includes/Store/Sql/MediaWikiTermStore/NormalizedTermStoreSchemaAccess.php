<?php

namespace Wikibase\Lib\Store\Sql\MediaWikiTermStore;

/**
 * Accessor to the entity-type-agnostic normalized term store schema
 *
 * Consumers can use per-storage-type implementations of this
 * accessor to acquire ids for stored terms to be used to link
 * entities with those term ids.
 */
interface NormalizedTermStoreSchemaAccess {

	/**
	 * acquires term_in_lang ids for given terms, inserting non-existing ones.
	 *
	 * @param array $termsArray terms per type per language:
	 * 	[
	 *		'type' => [
	 *			[ 'language' => 'term' | [ 'term1', 'term2', ... ] ], ...
	 *		], ...
	 *  ]
	 *
	 * @return array returns ids of acquired terms in the store
	 */
	public function acquireTerms( array $termsArray );

}
