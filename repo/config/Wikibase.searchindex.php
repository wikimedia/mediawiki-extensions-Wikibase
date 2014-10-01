<?php

use DataValues\MonolingualTextValue;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\EntityContent;

/**
 * Example showing how the search index behavior for Wikibase entities
 * can be controlled using the WikibaseTextForSearchIndex hook.
 *
 * If this config file is included, the search index will include
 * all string and text values present in the main snak of any
 * statement associated with an item.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

$wgHooks['WikibaseTextForSearchIndex'][] = function( EntityContent $entityContent, &$text ) {
	$entity = $entityContent->getEntity();

	if ( !( $entity instanceof Item ) ) {
		return true;
	}

	$statements = $entity->getStatements();

	/** @var Statement $statement */
	foreach ( $statements as $statement ) {
		$snak = $statement->getMainSnak();

		// Skip "no value" and "some value" snaks.
		if ( !( $snak instanceof PropertyValueSnak ) ) {
			continue;
		}

		$value = $snak->getDataValue();

		// If the value is text-like, give it to the indexer.
		if ( $value instanceof StringValue ) {
			$text .= $value->getValue() . "\n";
		} elseif ( $value instanceof MonolingualTextValue ) {
			$text .= $value->getText() . "\n";
		}
	}

	return true;
};
