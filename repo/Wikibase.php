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

if ( version_compare( $wgVersion, '1.19c', '<' ) ) { // Needs to be 1.19c because version_compare() works in confusing ways.
	die( '<b>Error:</b> Wikibase requires MediaWiki 1.19 or above.' );
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
$wgGroupPermissions['wbeditor']['item-add']			= true;
$wgGroupPermissions['wbeditor']['item-update']		= true;
$wgGroupPermissions['wbeditor']['item-set']			= true;
$wgGroupPermissions['wbeditor']['item-delete']		= true; // could be called remove
$wgGroupPermissions['wbeditor']['alias-add']		= true;
$wgGroupPermissions['wbeditor']['alias-update']		= true;
$wgGroupPermissions['wbeditor']['alias-set']		= true;
$wgGroupPermissions['wbeditor']['alias-delete']		= true; // could be called remove
$wgGroupPermissions['wbeditor']['site-link-add']	= true;
$wgGroupPermissions['wbeditor']['site-link-update']	= true;
$wgGroupPermissions['wbeditor']['site-link-set']	= true;
$wgGroupPermissions['wbeditor']['site-link-remove']	= true; // could be called remove
$wgGroupPermissions['wbeditor']['lang-attr-add']	= true;
$wgGroupPermissions['wbeditor']['lang-attr-update']	= true;
$wgGroupPermissions['wbeditor']['lang-attr-set']	= true;
$wgGroupPermissions['wbeditor']['lang-attr-delete']	= true; // could be called remove

// rights
// names should be according to other naming scheme
$wgGroupPermissions['wbeditor']['item-add']			= true;
$wgGroupPermissions['wbeditor']['item-update']		= true;
$wgGroupPermissions['wbeditor']['item-set']			= true;
$wgGroupPermissions['wbeditor']['item-delete']		= true; // could be called remove
$wgGroupPermissions['wbeditor']['alias-add']		= true;
$wgGroupPermissions['wbeditor']['alias-update']		= true;
$wgGroupPermissions['wbeditor']['alias-set']		= true;
$wgGroupPermissions['wbeditor']['alias-delete']		= true; // could be called remove
$wgGroupPermissions['wbeditor']['site-link-add']	= true;
$wgGroupPermissions['wbeditor']['site-link-update']	= true;
$wgGroupPermissions['wbeditor']['site-link-set']	= true;
$wgGroupPermissions['wbeditor']['site-link-delete']	= true; // could be called remove
$wgGroupPermissions['wbeditor']['lang-attr-add']	= true;
$wgGroupPermissions['wbeditor']['lang-attr-update']	= true;
$wgGroupPermissions['wbeditor']['lang-attr-set']	= true;
$wgGroupPermissions['wbeditor']['lang-attr-delete']	= true; // could be called remove

// i18n
$wgExtensionMessagesFiles['Wikibase'] 		= $dir . 'Wikibase.i18n.php';
$wgExtensionMessagesFiles['WikibaseAlias'] 	= $dir . 'Wikibase.i18n.alias.php';
$wgExtensionMessagesFiles['WikibaseNS'] 	= $dir . 'Wikibase.i18n.namespaces.php';



// Autoloading
$wgAutoloadClasses['WBSettings'] 						= $dir . 'Wikibase.settings.php';
$wgAutoloadClasses['WikibaseHooks'] 					= $dir . 'Wikibase.hooks.php';

// includes
$wgAutoloadClasses['WikibaseContentHandler'] 			= $dir . 'includes/WikibaseContentHandler.php';
$wgAutoloadClasses['WikibaseDifferenceEngine'] 			= $dir . 'includes/WikibaseDifferenceEngine.php';
$wgAutoloadClasses['WikibaseUtils'] 					= $dir . 'includes/WikibaseUtils.php';
$wgAutoloadClasses['WikibaseItem'] 						= $dir . 'includes/WikibaseItem.php';
$wgAutoloadClasses['WikibaseEntity'] 					= $dir . 'includes/WikibaseEntity.php';
$wgAutoloadClasses['WikibaseItemDisambiguation'] 		= $dir . 'includes/WikibaseItemDisambiguation.php';
$wgAutoloadClasses['WikibaseItemStructuredSave'] 		= $dir . 'includes/WikibaseItemStructuredSave.php';
$wgAutoloadClasses['WikibaseItemView'] 					= $dir . 'includes/WikibaseItemView.php';

// includes/actions
$wgAutoloadClasses['WikibaseViewItemAction'] 			= $dir . 'includes/actions/WikibaseViewItemAction.php';
$wgAutoloadClasses['WikibaseEditItemAction'] 			= $dir . 'includes/actions/WikibaseEditItemAction.php';

// includes/api
$wgAutoloadClasses['ApiWikibaseGetItems'] 				= $dir . 'includes/api/ApiWikibaseGetItems.php';
$wgAutoloadClasses['ApiWikibaseGetItemId'] 				= $dir . 'includes/api/ApiWikibaseGetItemId.php';
$wgAutoloadClasses['ApiWikibaseGetSiteLinks'] 			= $dir . 'includes/api/ApiWikibaseGetSiteLinks.php';
$wgAutoloadClasses['ApiWikibaseSetLanguageAttribute'] 	= $dir . 'includes/api/ApiWikibaseSetLanguageAttribute.php';
$wgAutoloadClasses['ApiWikibaseDeleteLanguageAttribute']= $dir . 'includes/api/ApiWikibaseDeleteLanguageAttribute.php';
$wgAutoloadClasses['ApiWikibaseModifyItem'] 			= $dir . 'includes/api/ApiWikibaseModifyItem.php';
$wgAutoloadClasses['ApiWikibaseLinkSite'] 				= $dir . 'includes/api/ApiWikibaseLinkSite.php';
$wgAutoloadClasses['ApiWikibaseSetAliases'] 			= $dir . 'includes/api/ApiWikibaseSetAliases.php';
$wgAutoloadClasses['ApiWikibaseSetItem'] 				= $dir . 'includes/api/ApiWikibaseSetItem.php';

// includes/specials
$wgAutoloadClasses['SpecialCreateItem'] 				= $dir . 'includes/specials/SpecialCreateItem.php';
$wgAutoloadClasses['SpecialItemByTitle'] 				= $dir . 'includes/specials/SpecialItemByTitle.php';
$wgAutoloadClasses['SpecialItemResolver'] 				= $dir . 'includes/specials/SpecialItemResolver.php';
$wgAutoloadClasses['SpecialItemByLabel'] 				= $dir . 'includes/specials/SpecialItemByLabel.php';



// tests
$wgAutoloadClasses['ApiWikibaseModifyItemTest'] 		= $dir . 'tests/phpunit/includes/api/ApiWikibaseModifyItemTest.php';



// API module registration
$wgAPIModules['wbgetitems'] 						= 'ApiWikibaseGetItems';
$wgAPIModules['wbgetitemid'] 						= 'ApiWikibaseGetItemId';
$wgAPIModules['wbsetlanguageattribute'] 			= 'ApiWikibaseSetLanguageAttribute';
$wgAPIModules['wbdeletelanguageattribute'] 			= 'ApiWikibaseDeleteLanguageAttribute';
$wgAPIModules['wbgetsitelinks'] 					= 'ApiWikibaseGetSiteLinks';
$wgAPIModules['wblinksite'] 						= 'ApiWikibaseLinkSite';
$wgAPIModules['wbsetaliases'] 						= 'ApiWikibaseSetAliases';
$wgAPIModules['wbsetitem'] 							= 'ApiWikibaseSetItem';



// Special page registration
//$wgSpecialPages['CreateItem'] 						= 'SpecialCreateItem';
$wgSpecialPages['ItemByTitle'] 						= 'SpecialItemByTitle';
$wgSpecialPages['ItemByLabel'] 						= 'SpecialItemByLabel';

// Hooks
$wgHooks['LoadExtensionSchemaUpdates'][] 			= 'WikibaseHooks::onSchemaUpdate';
$wgHooks['UnitTestsList'][] 						= 'WikibaseHooks::registerUnitTests';
$wgHooks['PageContentLanguage'][]					= 'WikibaseHooks::onPageContentLanguage';
$wgHooks['ResourceLoaderTestModules'][]				= 'WikibaseHooks::onResourceLoaderTestModules';
$wgHooks['AbortMove'][]								= 'WikibaseHooks::onAbortMove';


// Resource loader modules
$moduleTemplate = array(
	'localBasePath' => dirname( __FILE__ ) . '/resources',
	// since 'WikidataRepo' extension was renamed to 'Wikibase', the directory should be renamed in the git repo first
	// after that this 'weird' regex can be removed
	// Newslash: the directory is not in the git repo. So properly clone it or rename the directory on your machine if you did it wrong.
	'remoteExtPath' =>  'Wikibase/resources'
);

// common styles independent from JavaScript being enabled or disabled
$wgResourceModules['wikibase.common'] = $moduleTemplate + array(
	'styles' => array(
		'wikibase.css'
	)
);

$wgResourceModules['wikibase'] = $moduleTemplate + array(
	'scripts' => array(
		'wikibase.js',
		'wikibase.Site.js'
	),
	'dependencies' => array(
		'wikibase.common'
	)
);

$wgResourceModules['wikibase.tests.qunit.testrunner'] = $moduleTemplate + array(
	'scripts' => '../tests/qunit/data/testrunner.js',
	'dependencies' => array(
		'mediawiki.tests.qunit.testrunner',
	),
	'position' => 'top'
);

$wgResourceModules['wikibase.ui.Toolbar'] = $moduleTemplate + array(
	'scripts' => array(
		'wikibase.ui.js',
		'wikibase.ui.Tooltip.js',
		'wikibase.ui.Toolbar.js',
		'wikibase.ui.Toolbar.Group.js',
		'wikibase.ui.Toolbar.Label.js',
		'wikibase.ui.Toolbar.Button.js'
	),
	'styles' => array(
		'wikibase.ui.Toolbar.css'
	),
	'dependencies' => array(
		'jquery.tipsy',
		'mediawiki.legacy.shared',
		'jquery.ui.core'
	),
	'messages' => array(
		'wikibase-tooltip-error-details'
	)
);

$wgResourceModules['wikibase.ui.PropertyEditTool'] = $moduleTemplate + array(
	'scripts' => array(
		'wikibase.ui.js',
		'wikibase.ui.Toolbar.EditGroup.js', // related to EditableValue, see todo in file
		'wikibase.ui.PropertyEditTool.js',
		'wikibase.ui.PropertyEditTool.EditableValue.js',
		'wikibase.ui.PropertyEditTool.EditableValue.Interface.js',
		'wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.js',
		'wikibase.ui.PropertyEditTool.EditableValue.SitePageInterface.js',
		'wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface.js',
		'wikibase.ui.PropertyEditTool.EditableDescription.js',
		'wikibase.ui.PropertyEditTool.EditableLabel.js',
		'wikibase.ui.PropertyEditTool.EditableSiteLink.js',
		'wikibase.ui.LabelEditTool.js',
		'wikibase.ui.DescriptionEditTool.js',
		'wikibase.ui.SiteLinksEditTool.js',
		'wikibase.startup.js' // should probably be adjusted for more moduldarity
	),
	'styles' => array(
		'wikibase.ui.PropertyEditTool.css'
	),
	'dependencies' => array(
		'wikibase',
		'wikibase.ui.Toolbar',
		'jquery.ui.autocomplete',
		'mediawiki.Title',
		'mediawiki.jqueryMsg' // for {{plural}} and {{gender}} support in messages
	),
	'messages' => array(
		'wikibase-sitelinks',
		'wikibase-cancel',
		'wikibase-edit',
		'wikibase-save',
		'wikibase-add',
		'wikibase-label-edit-placeholder',
		'wikibase-description-edit-placeholder',
		'wikibase-sitelink-site-edit-placeholder',
		'wikibase-sitelink-page-edit-placeholder',
		'wikibase-label-input-help-message',
		'wikibase-description-input-help-message',
		'wikibase-sitelinks-input-help-message',
		'wikibase-remove',
		'wikibase-propertyedittool-full',
		'wikibase-propertyedittool-counter',
		'wikibase-propertyedittool-counter-pending',
		'wikibase-propertyedittool-counter-pending-pendingsubpart',
		'wikibase-propertyedittool-counter-pending-tooltip',
		'wikibase-sitelinksedittool-full',
		'wikibase-error-save-generic',
		'wikibase-error-save-connection',
		'wikibase-error-save-timeout'
	)
);

unset( $moduleTemplate );

// register hooks and handlers
define( 'CONTENT_MODEL_WIKIBASE', 'wikibase' );
$wgContentHandlers[CONTENT_MODEL_WIKIBASE] = 'WikibaseContentHandler';

define( 'WB_NS_DATA', 100 );
define( 'WB_NS_DATA_TALK', 101 );

$wgExtraNamespaces[WB_NS_DATA] = 'Data';
$wgExtraNamespaces[WB_NS_DATA_TALK] = 'Data_talk';

$wgNamespaceContentModels[WB_NS_DATA] = CONTENT_MODEL_WIKIBASE;


$egWBSettings = array();

$egWBSettings['apiDebugWithWrite'] = true;
$egWBSettings['apiDebugWithPost'] = true;
$egWBSettings['apiDebugWithRights'] = true;
