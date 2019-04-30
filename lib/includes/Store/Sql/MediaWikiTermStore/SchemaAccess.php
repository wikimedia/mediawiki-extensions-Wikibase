<?php

namespace Wikibase\Lib\Store\Sql\MediaWikiTermStore;

interface NormalizedTermStoreSchemaAccess {

	/**
	 * set property terms for.
	 *
	 * @param array $termsArray array can contain one of the following top-level keys:
	 * * 'label' => array of langauge code => term text
	 * * 'description' => array of language code => term text
	 * * 'alias' => array of langauge code => array of alises strings
	 */
	public function storeTerms( array $termsArray );

	/**
	 * clear terms of any type or language on a property
	 *
	 * @param array $termsArray array can contain one of the following top-level keys:
	 * * 'label' => array of langauge code => term text
	 * * 'description' => array of language code => term text
	 * * 'alias' => array of langauge code => array of alises strings
	 */
	public function removeTerms( array $termsArray );

}
