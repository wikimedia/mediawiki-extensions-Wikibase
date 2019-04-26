<?php

namespace Wikibase\Lib\Store\Sql\MediaWikiTermStore;

interface SchemaAccess {

	/**
	 * link a label text to a property for a given language
	 *
	 * @param int    $propertyId numeric id of the property
	 * @param string $lang language code of the label
	 * @param string $text label text
	 */
	public function setPropertyLabel( $propertyId, $lang, $text );

	/**
	 * link a description text to a property for a given language
	 *
	 * @param int    $propertyId numeric id of the property
	 * @param string $lang language code of the description
	 * @param string $text description text
	 */
	public function setPropertyDescription( $propertyId, $lang, $text );

	/**
	 * link an alias text to a property for a given language
	 *
	 * @param int    $propertyId numeric id of the property
	 * @param string $lang language code of the alias
	 * @param string $text alias text
	 */
	public function setPropertyAlias( $propertyId, $lang, $text );

}
