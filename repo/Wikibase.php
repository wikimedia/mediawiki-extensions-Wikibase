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
$wgGroupPermissions['*']['item-override']		= true;
$wgGroupPermissions['*']['item-create']			= true;
$wgGroupPermissions['*']['item-remove']			= true;
$wgGroupPermissions['*']['alias-add']			= true;
$wgGroupPermissions['*']['alias-set']			= true;
$wgGroupPermissions['*']['alias-remove']		= true;
$wgGroupPermissions['*']['sitelink-remove']		= true;
$wgGroupPermissions['*']['sitelink-update']		= true;
$wgGroupPermissions['*']['label-remove']		= true;
$wgGroupPermissions['*']['label-update']		= true;
$wgGroupPermissions['*']['description-remove']	= true;
$wgGroupPermissions['*']['description-update']	= true;

// i18n
$wgExtensionMessagesFiles['Wikibase'] 		= $dir . 'Wikibase.i18n.php';
$wgExtensionMessagesFiles['WikibaseAlias'] 	= $dir . 'Wikibase.i18n.alias.php';
$wgExtensionMessagesFiles['WikibaseNS'] 	= $dir . 'Wikibase.i18n.namespaces.php';


// Autoloading
$wgAutoloadClasses['WikibaseHooks'] 					= $dir . 'Wikibase.hooks.php';

// includes
$wgAutoloadClasses['Wikibase\ItemDiffView'] 			= $dir . 'includes/ItemDiffView.php';
$wgAutoloadClasses['Wikibase\ItemDisambiguation'] 		= $dir . 'includes/ItemDisambiguation.php';
$wgAutoloadClasses['Wikibase\ItemView'] 				= $dir . 'includes/ItemView.php';

// includes/actions
$wgAutoloadClasses['Wikibase\ViewItemAction'] 			= $dir . 'includes/actions/ViewItemAction.php';
$wgAutoloadClasses['Wikibase\EditItemAction'] 			= $dir . 'includes/actions/EditItemAction.php';

// includes/api
$wgAutoloadClasses['Wikibase\Api'] 						= $dir . 'includes/api/Api.php';
$wgAutoloadClasses['Wikibase\ApiGetItems'] 				= $dir . 'includes/api/ApiGetItems.php';
$wgAutoloadClasses['Wikibase\ApiSetLanguageAttribute'] 	= $dir . 'includes/api/ApiSetLanguageAttribute.php';
$wgAutoloadClasses['Wikibase\ApiModifyItem'] 			= $dir . 'includes/api/ApiModifyItem.php';
$wgAutoloadClasses['Wikibase\ApiSetSiteLink'] 			= $dir . 'includes/api/ApiSetSiteLink.php';
$wgAutoloadClasses['Wikibase\ApiSetAliases'] 			= $dir . 'includes/api/ApiSetAliases.php';
$wgAutoloadClasses['Wikibase\ApiSetItem'] 				= $dir . 'includes/api/ApiSetItem.php';

// includes/content
$wgAutoloadClasses['Wikibase\EntityContent'] 			= $dir . 'includes/content/EntityContent.php';
$wgAutoloadClasses['Wikibase\EntityHandler'] 			= $dir . 'includes/content/EntityHandler.php';
$wgAutoloadClasses['Wikibase\ItemContent'] 				= $dir . 'includes/content/ItemContent.php';
$wgAutoloadClasses['Wikibase\ItemHandler'] 				= $dir . 'includes/content/ItemHandler.php';
$wgAutoloadClasses['Wikibase\PropertyContent'] 			= $dir . 'includes/content/PropertyContent.php';
$wgAutoloadClasses['Wikibase\PropertyHandler'] 			= $dir . 'includes/content/PropertyHandler.php';
$wgAutoloadClasses['Wikibase\QueryContent'] 			= $dir . 'includes/content/QueryContent.php';
$wgAutoloadClasses['Wikibase\QueryHandler'] 			= $dir . 'includes/content/QueryHandler.php';

// includes/specials
$wgAutoloadClasses['SpecialCreateItem'] 				= $dir . 'includes/specials/SpecialCreateItem.php';
$wgAutoloadClasses['SpecialItemByTitle'] 				= $dir . 'includes/specials/SpecialItemByTitle.php';
$wgAutoloadClasses['SpecialItemResolver'] 				= $dir . 'includes/specials/SpecialItemResolver.php';
$wgAutoloadClasses['SpecialItemByLabel'] 				= $dir . 'includes/specials/SpecialItemByLabel.php';
$wgAutoloadClasses['SpecialWikibasePage'] 				= $dir . 'includes/specials/SpecialWikibasePage.php';

// includes/updates
$wgAutoloadClasses['Wikibase\ItemDeletionUpdate'] 		= $dir . 'includes/updates/ItemDeletionUpdate.php';
$wgAutoloadClasses['Wikibase\ItemStructuredSave'] 		= $dir . 'includes/updates/ItemStructuredSave.php';

// tests
$wgAutoloadClasses['Wikibase\Test\TestItemContents'] 		= $dir . 'tests/phpunit/TestItemContents.php';
$wgAutoloadClasses['Wikibase\Test\ApiModifyItemBase'] 		= $dir . 'tests/phpunit/includes/api/ApiModifyItemBase.php';
$wgAutoloadClasses['Wikibase\Test\SpecialPageTestBase'] 	= $dir . 'tests/phpunit/includes/specials/SpecialPageTestBase.php';

// API module registration
$wgAPIModules['wbgetitems'] 						= 'Wikibase\ApiGetItems';
$wgAPIModules['wbsetlanguageattribute'] 			= 'Wikibase\ApiSetLanguageAttribute';
$wgAPIModules['wbsetsitelink'] 						= 'Wikibase\ApiSetSiteLink';
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
$wgHooks['ArticleDeleteComplete'][] 				= 'WikibaseHooks::onArticleDeleteComplete';
$wgHooks['LinkBegin'][] 							= 'WikibaseHooks::onLinkBegin';
$wgHooks['OutputPageBodyAttributes'][] 				= 'WikibaseHooks::onOutputPageBodyAttributes';
$wgHooks['GetPreferences'][]						= 'WikibaseHooks::onGetPreferences';
$wgHooks['UserGetDefaultOptions'][]					= 'WikibaseHooks::onUserGetDefaultOptions';
$wgHooks['FormatAutocomments'][]					= 'WikibaseHooks::onFormatAutocomments';



// Resource Loader Modules:
$wgResourceModules = array_merge( $wgResourceModules, include( "$dir/resources/Resources.php" ) );


// register hooks and handlers
$wgContentHandlers[CONTENT_MODEL_WIKIBASE_ITEM] = '\Wikibase\ItemHandler';
$wgContentHandlers[CONTENT_MODEL_WIKIBASE_PROPERTY] = '\Wikibase\PropertyHandler';
$wgContentHandlers[CONTENT_MODEL_WIKIBASE_QUERY] = '\Wikibase\QueryHandler';

unset( $dir );
