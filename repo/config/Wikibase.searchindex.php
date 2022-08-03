<?php

use DataValues\MonolingualTextValue;
use DataValues\StringValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\Content\ItemContent;

/**
 * Example showing how the search index behavior for Wikibase entities
 * can be controlled using the WikibaseTextForSearchIndex hook.
 *
 * If this config file is included, the search index will include
 * all string and text values present in the main snak of any
 * statement associated with an item.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

$wgHooks['WikibaseTextForSearchIndex'][] = function( EntityContent $entityContent, &$text ) {
	if ( !( $entityContent instanceof ItemContent ) ) {
		return;
	}

	$statements = $entityContent->getItem()->getStatements();

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
};
