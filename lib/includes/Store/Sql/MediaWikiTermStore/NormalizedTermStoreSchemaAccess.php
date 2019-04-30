<?php

namespace Wikibase\Lib\Store\Sql\MediaWikiTermStore;

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
	 * @return array returns wbtl_id values of wbt_term_in_lang records of acquired terms, 
	 * split between those that were newly inserted and those that existed already:
	 *	[
	 *		'new' => [ 8, 9, 10, 100, ... ],
	 *		'old' => [ 2, 3, 20, 200, ... ]
	 *	]
	 */
	public function acquireTerms( array $termsArray );

}
