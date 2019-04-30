<?php

namespace Wikibase\Lib\Store\Sql\MediaWikiTermStore;

interface SchemaAccess {

	/**
	 * set property terms for a property id.
	 *
	 * @param int $propertyId
	 * @param array $termsArray array can contain one of the following top-level keys:
	 * * 'label' => array of langauge code => term text
	 * * 'description' => array of language code => term text
	 * * 'alias' => array of langauge code => array of alises strings
	 */
	public function setPropertyTerms( $propertyId, array $termsArray );

	/**
	 * clear terms of any type or language on a property
	 */
	public function clearPropertyTerms( $propertyId );

}
