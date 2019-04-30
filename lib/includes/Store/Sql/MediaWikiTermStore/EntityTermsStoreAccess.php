<?php

namespace Wikibase\Lib\Store\Sql\MediaWikiTermStore;

interface EntityTermsStoreSchemaAccess {

	/**
	 * set property terms for.
	 *
	 * @param int $entityNumericId
	 * @param string $entityType
	 * @param array $termsArray array can contain one of the following top-level keys:
	 * * 'label' => array of langauge code => term text
	 * * 'description' => array of language code => term text
	 * * 'alias' => array of langauge code => array of alises strings
	 */
	public function setTerms( $entityNumericId, string $entityType, array $termsArray );

	/**
	 * clear terms of any type or language on a property
	 */
	public function clearPropertyTerms( $propertyId );

}
