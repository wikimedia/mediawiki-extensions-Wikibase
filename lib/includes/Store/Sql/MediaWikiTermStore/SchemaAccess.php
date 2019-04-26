<?php

namespace Wikibase\Lib\Store\Sql\MediaWikiTermStore;

interface SchemaAccess {

	/**
	 * set a label text for a property for a given language
	 *
	 * @param int $propertyId numeric id of the property
	 * @param string $lang language code of the label
	 * @param string $text label text
	 */
	public function setPropertyLabel( $propertyId, $lang, $text );

	/**
	 * set a description text for a property for a given language
	 *
	 * @param int $propertyId numeric id of the property
	 * @param string $lang language code of the description
	 * @param string $text description text
	 */
	public function setPropertyDescription( $propertyId, $lang, $text );

	/**
	 * set an alias text for a property for a given language
	 *
	 * @param int $propertyId numeric id of the property
	 * @param string $lang language code of the alias
	 * @param string $text alias text
	 */
	public function setPropertyAlias( $propertyId, $lang, $text );

	/**
	 * clear terms of any type or language on a property
	 */
	public function clearPropertyTerms( $propertyId );
}
