<?php

/**
 * Initialization file for the Wikibase extension.
 *
 * Documentation:	 		https://www.mediawiki.org/wiki/Extension:Wikibase
 * Support					https://www.mediawiki.org/wiki/Extension_talk:Wikibase
 * Source code:				https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/extensions/WikidataRepo.git
 *
 * @file Wikibase.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 * @author Daniel Kinzler
 */

/**
 * This documentation group collects source code files belonging to Wikibase.
 *
 * @defgroup Wikibase Wikibase
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

if ( version_compare( $wgVersion, '1.20c', '<' ) ) { // Needs to be 1.20c because version_compare() works in confusing ways.
	die( '<b>Error:</b> Wikibase requires MediaWiki 1.20 or above.' );
}

if ( !defined( 'WBL_VERSION' ) ) { // No version constant to check against :/
	die( '<b>Error:</b> Wikibase depends on the <a href="https://www.mediawiki.org/wiki/Extension:WikibaseLib">WikibaseLib</a> extension.' );
}

// TODO: enable
//if ( !array_key_exists( 'CountryNames', $wgAutoloadClasses ) ) { // No version constant to check against :/
//	die( '<b>Error:</b> Wikibase depends on the <a href="https://www.mediawiki.org/wiki/Extension:CLDR">CLDR</a> extension.' );
//}

define( 'WB_VERSION', '0.1 alpha' );

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Wikibase',
	'version' => WB_VERSION,
	'author' => array(
		'The Wikidata team', // TODO: link?
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:Wikibase',
	'descriptionmsg' => 'wikibase-desc'
);

$dir = dirname( __FILE__ ) . '/';

// rights
// names should be according to other naming scheme
$wgGroupPermissions['*']['item-add']			= true;
$wgGroupPermissions['*']['item-update']			= true;
$wgGroupPermissions['*']['item-set']			= true;
$wgGroupPermissions['*']['item-remove']			= true;
$wgGroupPermissions['*']['alias-add']			= true;
$wgGroupPermissions['*']['alias-update']		= true;
$wgGroupPermissions['*']['alias-set']			= true;
$wgGroupPermissions['*']['alias-remove']		= true;
$wgGroupPermissions['*']['site-link-add']		= true;
$wgGroupPermissions['*']['site-link-update']	= true;
$wgGroupPermissions['*']['site-link-set']		= true;
$wgGroupPermissions['*']['site-link-remove']	= true;
$wgGroupPermissions['*']['lang-attr-add']		= true;
$wgGroupPermissions['*']['lang-attr-update']	= true;
$wgGroupPermissions['*']['lang-attr-set']		= true;
$wgGroupPermissions['*']['lang-attr-remove']	= true;

// i18n
$wgExtensionMessagesFiles['Wikibase'] 		= $dir . 'Wikibase.i18n.php';
$wgExtensionMessagesFiles['WikibaseAlias'] 	= $dir . 'Wikibase.i18n.alias.php';
$wgExtensionMessagesFiles['WikibaseNS'] 	= $dir . 'Wikibase.i18n.namespaces.php';


// Autoloading
$wgAutoloadClasses['WikibaseHooks'] 					= $dir . 'Wikibase.hooks.php';

// includes
$wgAutoloadClasses['Wikibase\ChangeNotifier'] 			= $dir . 'includes/ChangeNotifier.php';
$wgAutoloadClasses['Wikibase\ItemDiffView'] 			= $dir . 'includes/ItemDiffView.php';
$wgAutoloadClasses['Wikibase\ItemHandler'] 				= $dir . 'includes/ItemHandler.php';
$wgAutoloadClasses['Wikibase\ItemDeletionUpdate'] 		= $dir . 'includes/ItemDeletionUpdate.php';
$wgAutoloadClasses['Wikibase\EntityHandler'] 			= $dir . 'includes/EntityHandler.php';
$wgAutoloadClasses['Wikibase\ItemDisambiguation'] 		= $dir . 'includes/ItemDisambiguation.php';
$wgAutoloadClasses['Wikibase\ItemStructuredSave'] 		= $dir . 'includes/ItemStructuredSave.php';
$wgAutoloadClasses['Wikibase\ItemView'] 				= $dir . 'includes/ItemView.php';

// includes/actions
$wgAutoloadClasses['Wikibase\ViewItemAction'] 			= $dir . 'includes/actions/ViewItemAction.php';
$wgAutoloadClasses['Wikibase\EditItemAction'] 			= $dir . 'includes/actions/EditItemAction.php';

// includes/api
$wgAutoloadClasses['Wikibase\Api'] 						= $dir . 'includes/api/Api.php';
$wgAutoloadClasses['Wikibase\ApiGetItems'] 				= $dir . 'includes/api/ApiGetItems.php';
$wgAutoloadClasses['Wikibase\ApiGetItemId'] 			= $dir . 'includes/api/ApiGetItemId.php';
$wgAutoloadClasses['Wikibase\ApiGetSiteLinks'] 			= $dir . 'includes/api/ApiGetSiteLinks.php';
$wgAutoloadClasses['Wikibase\ApiSetLanguageAttribute'] 	= $dir . 'includes/api/ApiSetLanguageAttribute.php';
$wgAutoloadClasses['Wikibase\ApiDeleteLanguageAttribute']= $dir . 'includes/api/ApiDeleteLanguageAttribute.php';
$wgAutoloadClasses['Wikibase\ApiModifyItem'] 			= $dir . 'includes/api/ApiModifyItem.php';
$wgAutoloadClasses['Wikibase\ApiLinkSite'] 				= $dir . 'includes/api/ApiLinkSite.php';
$wgAutoloadClasses['Wikibase\ApiSetAliases'] 			= $dir . 'includes/api/ApiSetAliases.php';
$wgAutoloadClasses['Wikibase\ApiSetItem'] 				= $dir . 'includes/api/ApiSetItem.php';

// includes/specials
$wgAutoloadClasses['SpecialCreateItem'] 				= $dir . 'includes/specials/SpecialCreateItem.php';
$wgAutoloadClasses['SpecialItemByTitle'] 				= $dir . 'includes/specials/SpecialItemByTitle.php';
$wgAutoloadClasses['SpecialItemResolver'] 				= $dir . 'includes/specials/SpecialItemResolver.php';
$wgAutoloadClasses['SpecialItemByLabel'] 				= $dir . 'includes/specials/SpecialItemByLabel.php';
$wgAutoloadClasses['SpecialWikibasePage'] 				= $dir . 'includes/specials/SpecialWikibasePage.php';

// tests
$wgAutoloadClasses['Wikibase\Test\ApiModifyItemBase'] 		= $dir . 'tests/phpunit/includes/api/ApiModifyItemBase.php';
$wgAutoloadClasses['Wikibase\Test\SpecialPageTestBase'] 	= $dir . 'tests/phpunit/includes/specials/SpecialPageTestBase.php';

// API module registration
$wgAPIModules['wbgetitems'] 						= 'Wikibase\ApiGetItems';
$wgAPIModules['wbgetitemid'] 						= 'Wikibase\ApiGetItemId';
$wgAPIModules['wbsetlanguageattribute'] 			= 'Wikibase\ApiSetLanguageAttribute';
$wgAPIModules['wbdeletelanguageattribute'] 			= 'Wikibase\ApiDeleteLanguageAttribute';
$wgAPIModules['wbgetsitelinks'] 					= 'Wikibase\ApiGetSiteLinks';
$wgAPIModules['wblinksite'] 						= 'Wikibase\ApiLinkSite';
$wgAPIModules['wbsetaliases'] 						= 'Wikibase\ApiSetAliases';
$wgAPIModules['wbsetitem'] 							= 'Wikibase\ApiSetItem';


// Special page registration
$wgSpecialPages['CreateItem'] 						= 'SpecialCreateItem';
$wgSpecialPages['ItemByTitle'] 						= 'SpecialItemByTitle';
$wgSpecialPages['ItemByLabel'] 						= 'SpecialItemByLabel';


// Hooks
$wgHooks['WikibaseDefaultSettings'][] 			    = 'WikibaseHooks::onWikibaseDefaultSettings';
$wgHooks['LoadExtensionSchemaUpdates'][] 			= 'WikibaseHooks::onSchemaUpdate';
$wgHooks['UnitTestsList'][] 						= 'WikibaseHooks::registerUnitTests';
$wgHooks['PageContentLanguage'][]					= 'WikibaseHooks::onPageContentLanguage';
$wgHooks['ResourceLoaderTestModules'][]				= 'WikibaseHooks::onResourceLoaderTestModules';
$wgHooks['NamespaceIsMovable'][]					= 'WikibaseHooks::onNamespaceIsMovable';
$wgHooks['NewRevisionFromEditComplete'][]			= 'WikibaseHooks::onNewRevisionFromEditComplete';
$wgHooks['SkinTemplateNavigation'][] 				= 'WikibaseHooks::onPageTabs';


// Resource Loader Modules:
$wgResourceModules = array_merge( $wgResourceModules, include( "$dir/resources/Resources.php" ) );


// register hooks and handlers
$wgContentHandlers[CONTENT_MODEL_WIKIBASE_ITEM] = '\Wikibase\ItemHandler';

//@todo: FIXME: this doesn't give wikis a chance to change the $baseNs.
//       Namespace definitions should be deferred into a hook and be based on Wikibase\Settings.
//       Note that some wikis may use the main namespace for data, or not have a data namespace.
$baseNs = 100;

define( 'WB_NS_DATA', $baseNs );
define( 'WB_NS_DATA_TALK', $baseNs + 1 );
//define( 'WB_NS_PROPERTY', $baseNs + 2 );
//define( 'WB_NS_PROPERTY_TALK', $baseNs + 3 );
//define( 'WB_NS_QUERY', $baseNs + 4 );
//define( 'WB_NS_QUERY_TALK', $baseNs + 5 );

$wgExtraNamespaces[WB_NS_DATA] = 'Data';
$wgExtraNamespaces[WB_NS_DATA_TALK] = 'Data_talk';
//$wgExtraNamespaces[WB_NS_DATA] = 'Property';
//$wgExtraNamespaces[WB_NS_DATA_TALK] = 'Property_talk';
//$wgExtraNamespaces[WB_NS_DATA] = 'Query';
//$wgExtraNamespaces[WB_NS_DATA_TALK] = 'Query_talk';

$wgNamespacesToBeSearchedDefault[WB_NS_DATA] = true;

$wgNamespaceContentModels[WB_NS_DATA] = CONTENT_MODEL_WIKIBASE_ITEM;

unset( $dir );
