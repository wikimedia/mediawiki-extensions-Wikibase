<?php

/**
 * Example configuration for the Wikibase extension.
 *
 * This file is NOT an entry point the Wikibase extension. Use Wikibase.php.
 * It should furthermore not be included from outside the extension.
 *
 * @see docs/options.wiki
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

if ( !defined( 'WB_VERSION' ) ) {
	die( 'Not an entry point. Load Wikibase.php first.' );
}

call_user_func( function() {
	global $wgContentHandlerUseDB, $wgExtraNamespaces, $wgWBRepoSettings;
	global $wgDBname, $wgNamespacesToBeSearchedDefault;

	$wgContentHandlerUseDB = true;

	$baseNs = 120;

	// Define custom namespaces. Use these exact constant names.
	define( 'WB_NS_ITEM', $baseNs );
	define( 'WB_NS_ITEM_TALK', $baseNs + 1 );
	define( 'WB_NS_PROPERTY', $baseNs + 2 );
	define( 'WB_NS_PROPERTY_TALK', $baseNs + 3 );

	// Register extra namespaces.
	$wgExtraNamespaces[WB_NS_ITEM] = 'Item';
	$wgExtraNamespaces[WB_NS_ITEM_TALK] = 'Item_talk';
	$wgExtraNamespaces[WB_NS_PROPERTY] = 'Property';
	$wgExtraNamespaces[WB_NS_PROPERTY_TALK] = 'Property_talk';

	// Tell Wikibase which namespace to use for which kind of entity
	$wgWBRepoSettings['entityNamespaces']['item'] = WB_NS_ITEM;
	$wgWBRepoSettings['entityNamespaces']['property'] = WB_NS_PROPERTY;

	// Make sure we use the same keys on repo and clients, so we can share cached objects.
	$wgWBRepoSettings['sharedCacheKeyPrefix'] = $wgDBname . ':WBL/' . rawurlencode( WBL_VERSION );

	// NOTE: no need to set up $wgNamespaceContentModels, Wikibase will do that automatically based on $wgWBRepoSettings

	// Tell MediaWIki to search the item namespace
	$wgNamespacesToBeSearchedDefault[WB_NS_ITEM] = true;

	// the special group includes all the sites in the specialSiteLinkGroups,
	// grouped together in a 'Pages linked to other sites' section.
	$wgWBRepoSettings['siteLinkGroups'] = [
		'wikipedia',
		'wikinews',
		'wikiquote',
		'wikisource',
		'wikivoyage',
		'special'
	];

	// these are the site_group codes as listed in the sites table
	$wgWBRepoSettings['specialSiteLinkGroups'] = [ 'commons', 'wikidata' ];
} );

/*
// Include Wikibase.searchindex.php to include string and text values in the full text index:
require_once __DIR__ . '/Wikibase.searchindex.php';
*/

/*
// Alternative settings, using the main namespace for items.
// Note: if you do that, several core tests may fail. Parser tests for instance
// assume that the main namespace contains wikitext.
$baseNs = 120;

// NOTE: do *not* define WB_NS_ITEM and WB_NS_ITEM_TALK when using a core namespace for items!
define( 'WB_NS_PROPERTY', $baseNs +2 );
define( 'WB_NS_PROPERTY_TALK', $baseNs +3 );
define( 'WB_NS_QUERY', $baseNs +4 );
define( 'WB_NS_QUERY_TALK', $baseNs +5 );

// You can set up an alias for the main namespace, if you like.
//$wgNamespaceAliases['Item'] = NS_MAIN;
//$wgNamespaceAliases['Item_talk'] = NS_TALK;

// No extra namespace for items, using a core namespace for that.
$wgExtraNamespaces[WB_NS_PROPERTY] = 'Property';
$wgExtraNamespaces[WB_NS_PROPERTY_TALK] = 'Property_talk';
$wgExtraNamespaces[WB_NS_QUERY] = 'Query';
$wgExtraNamespaces[WB_NS_QUERY_TALK] = 'Query_talk';

// Tell Wikibase which namespace to use for which kind of entity
$wgWBRepoSettings['entityNamespaces']['item'] = NS_MAIN; // <=== Use main namespace for items!!!
$wgWBRepoSettings['entityNamespaces']['property'] = WB_NS_PROPERTY; // use custom namespace

// No need to mess with $wgNamespacesToBeSearchedDefault, the main namespace will be searched per default.

// Alternate setup for rights so editing of entities by default is off, while a logged in
// user can edit everything. An other interesting alternative is to let the anonymous user
// do everything except creating items and properties and setting rank.
// First block sets all rights for anonymous to false, that is they have no rights.
$wgGroupPermissions['*']['item-term'] = false;
$wgGroupPermissions['*']['item-merge'] = false;
$wgGroupPermissions['*']['property-term'] = false;
$wgGroupPermissions['*']['property-create'] = false;
// Second block sets all rights for anonymous to true, that is they hold the rights.
$wgGroupPermissions['user']['item-term'] = true;
$wgGroupPermissions['user']['item-merge'] = true;
$wgGroupPermissions['user']['property-term'] = true;
$wgGroupPermissions['user']['property-create'] = true;

*/
