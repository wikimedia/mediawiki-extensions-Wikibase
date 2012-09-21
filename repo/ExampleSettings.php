<?php

/**
 * Example configuration for the Wikibase extension.
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

// Define custom namespaces. Use these exact constant names.
$baseNs = 100;

define( 'WB_NS_ITEM', $baseNs );
define( 'WB_NS_ITEM_TALK', $baseNs + 1 );
define( 'WB_NS_PROPERTY', $baseNs + 2 );
define( 'WB_NS_PROPERTY_TALK', $baseNs + 3 );
define( 'WB_NS_QUERY', $baseNs + 4 );
define( 'WB_NS_QUERY_TALK', $baseNs + 5 );

// Register extra namespaces.
$wgExtraNamespaces[WB_NS_ITEM] = 'Item';
$wgExtraNamespaces[WB_NS_ITEM] = 'Item_talk';
$wgExtraNamespaces[WB_NS_PROPERTY] = 'Property';
$wgExtraNamespaces[WB_NS_PROPERTY_TALK] = 'Property_talk';
$wgExtraNamespaces[WB_NS_QUERY] = 'Query';
$wgExtraNamespaces[WB_NS_QUERY_TALK] = 'Query_talk';

// Tell Wikibase which namespace to use for which kind of entity
$egWBSettings['entityNamespaces'][CONTENT_MODEL_WIKIBASE_ITEM] = NS_MAIN;
$egWBSettings['entityNamespaces'][CONTENT_MODEL_WIKIBASE_PROPERTY] = WB_NS_PROPERTY;
$egWBSettings['entityNamespaces'][CONTENT_MODEL_WIKIBASE_QUERY] = WB_NS_QUERY;

// NOTE: no need to set up $wgNamespaceContentModels, Wikibase will do that automatically based on $egWBSettings

// Tell MediaWIki to search the item namespace
$wgNamespacesToBeSearchedDefault[WB_NS_ITEM] = true;

// More things to play with
$egWBSettings['apiInDebug'] = false;
$egWBSettings['apiInTest'] = false;
$egWBSettings['apiWithRights'] = true;
$egWBSettings['apiWithTokens'] = true;

$wgGroupPermissions['wbeditor']['item-set'] = true;



/*
// Alternative settings, using the main namespace for items.
// Note: if you do that, several core tests may fail. Parser tests for instance
// assume that the main namespace contains wikitext.
$baseNs = 100;

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
$egWBSettings['entityNamespaces'][CONTENT_MODEL_WIKIBASE_ITEM] = NS_MAIN; // <=== Use main namespace for items!!!
$egWBSettings['entityNamespaces'][CONTENT_MODEL_WIKIBASE_PROPERTY] = WB_NS_PROPERTY; // use custom namespace
$egWBSettings['entityNamespaces'][CONTENT_MODEL_WIKIBASE_QUERY] = WB_NS_QUERY; // use custom namespace

// No need to mess with $wgNamespacesToBeSearchedDefault, the main namespace will be searched per default.
*/
