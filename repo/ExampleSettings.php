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

$baseNs = 100;

define( 'WB_NS_DATA', $baseNs );
define( 'WB_NS_DATA_TALK', $baseNs + 1 );
define( 'WB_NS_PROPERTY', $baseNs + 2 );
define( 'WB_NS_PROPERTY_TALK', $baseNs + 3 );
define( 'WB_NS_QUERY', $baseNs + 4 );
define( 'WB_NS_QUERY_TALK', $baseNs + 5 );
define( 'WB_NS_TYPE', $baseNs + 6 );
define( 'WB_NS_TYPE_TALK', $baseNs + 7 );

$wgExtraNamespaces[WB_NS_DATA] = 'Data';
$wgExtraNamespaces[WB_NS_DATA_TALK] = 'Data_talk';
$wgExtraNamespaces[WB_NS_PROPERTY] = 'Property';
$wgExtraNamespaces[WB_NS_PROPERTY_TALK] = 'Property_talk';
$wgExtraNamespaces[WB_NS_QUERY] = 'Query';
$wgExtraNamespaces[WB_NS_QUERY_TALK] = 'Query_talk';
$wgExtraNamespaces[WB_NS_TYPE] = 'Type';
$wgExtraNamespaces[WB_NS_TYPE_TALK] = 'Type_talk';

$wgNamespacesToBeSearchedDefault[WB_NS_DATA] = true;
$wgNamespacesToBeSearchedDefault[WB_NS_PROPERTY] = true;
$wgNamespacesToBeSearchedDefault[WB_NS_QUERY] = true;
$wgNamespacesToBeSearchedDefault[WB_NS_TYPE] = true;

$wgNamespaceContentModels[WB_NS_DATA] = CONTENT_MODEL_WIKIBASE_ITEM;
$wgNamespaceContentModels[WB_NS_PROPERTY] = CONTENT_MODEL_WIKIBASE_PROPERTY;
$wgNamespaceContentModels[WB_NS_QUERY] = CONTENT_MODEL_WIKIBASE_QUERY;
$wgNamespaceContentModels[WB_NS_TYPE] = CONTENT_MODEL_WIKIBASE_TYPE;


$egWBSettings['apiInDebug'] = false;
$egWBSettings['apiWithRights'] = true;
$egWBSettings['apiWithTokens'] = true;
$egWBSettings['apiInTest'] = true;
$wgGroupPermissions['wbeditor']['item-set'] = true;