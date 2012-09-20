<?php

/**
 * Namespace internationalization for the Wikibase extension.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */

// For all well known Wikibase namespace constants, check if they are defined.
// If they are not defined, define them to be something otherwise unusable to get them out of the way.
// In effect this means that namespace translations apply only if the user defined the corresponding
// namespace constant.
$namespaceConstants = array(
	'WB_NS_DATA',     'WB_NS_DATA_TALK',  // legacy
	'WB_NS_ITEM',     'WB_NS_ITEM_TALK',
	'WB_NS_PROPERTY', 'WB_NS_PROPERTY_TALK',
	'WB_NS_QUERY',    'WB_NS_QUERY_TALK',
);

//@todo: relying on these constants to be defined or not is a pretty horrible hack.
//      these constants are not used anywhere else, they are expected to come from LocalSettings,
//      where the user has to know that the namespace constants *have* to have these names. Ugh.

foreach ( $namespaceConstants as $const ) {
	if ( !defined( $const ) ) {
		// define constant to be something that doesn't hurt.
		// let's hope nothing else is using that :)
		define( $const, -99999 );
	}
}

$namespaceNames = array();

$namespaceNames['en'] = array(
	WB_NS_DATA      => 'Data',      // legacy
	WB_NS_DATA_TALK => 'Data_talk', // legacy

	WB_NS_ITEM      => 'Item',
	WB_NS_ITEM_TALK => 'Item_talk',

	WB_NS_PROPERTY      => 'Property',
	WB_NS_PROPERTY_TALK => 'Property_talk',

	WB_NS_QUERY      => 'Query',
	WB_NS_QUERY_TALK => 'Query_talk',
);

$namespaceNames['de'] = array(
	WB_NS_DATA      => 'Daten',           // legacy
	WB_NS_DATA_TALK => 'Datendiskussion', // legacy

	WB_NS_ITEM      => 'Thema',
	WB_NS_ITEM_TALK => 'Themendiskussion',

	WB_NS_PROPERTY      => 'Eigenschaft',
	WB_NS_PROPERTY_TALK => 'Eigenschaftsdiskussion',

	WB_NS_QUERY      => 'Abfrage',
	WB_NS_QUERY_TALK => 'Abfragediskussion',
);
