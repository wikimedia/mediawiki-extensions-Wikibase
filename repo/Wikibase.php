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
$wgAutoloadClasses['WBSettings'] 						= $dir . 'Wikibase.settings.php';
$wgAutoloadClasses['WikibaseHooks'] 					= $dir . 'Wikibase.hooks.php';

// includes
$wgAutoloadClasses['Wikibase\DifferenceEngine'] 		= $dir . 'includes/DifferenceEngine.php';
$wgAutoloadClasses['Wikibase\Utils'] 					= $dir . 'includes/Utils.php';
$wgAutoloadClasses['Wikibase\Item'] 					= $dir . 'includes/Item.php';
$wgAutoloadClasses['Wikibase\ItemDiff'] 				= $dir . 'includes/ItemDiff.php';
$wgAutoloadClasses['Wikibase\ItemHandler'] 				= $dir . 'includes/ItemHandler.php';
$wgAutoloadClasses['Wikibase\Entity'] 					= $dir . 'includes/Entity.php';
$wgAutoloadClasses['Wikibase\EntityDiff'] 				= $dir . 'includes/EntityDiff.php';
$wgAutoloadClasses['Wikibase\EntityHandler'] 			= $dir . 'includes/EntityHandler.php';
$wgAutoloadClasses['Wikibase\ItemDisambiguation'] 		= $dir . 'includes/ItemDisambiguation.php';
$wgAutoloadClasses['Wikibase\ItemStructuredSave'] 		= $dir . 'includes/ItemStructuredSave.php';
$wgAutoloadClasses['Wikibase\ItemView'] 				= $dir . 'includes/ItemView.php';
$wgAutoloadClasses['Wikibase\Sites'] 					= $dir . 'includes/Sites.php';
$wgAutoloadClasses['Wikibase\Site'] 					= $dir . 'includes/Site.php';

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
$wgAutoloadClasses['ApiWikibaseModifyItemTest'] 		= $dir . 'tests/phpunit/includes/api/ApiWikibaseModifyItemTest.php';



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
$wgHooks['LoadExtensionSchemaUpdates'][] 			= 'WikibaseHooks::onSchemaUpdate';
$wgHooks['UnitTestsList'][] 						= 'WikibaseHooks::registerUnitTests';
$wgHooks['PageContentLanguage'][]					= 'WikibaseHooks::onPageContentLanguage';
$wgHooks['ResourceLoaderTestModules'][]				= 'WikibaseHooks::onResourceLoaderTestModules';
$wgHooks['NamespaceIsMovable'][]					= 'WikibaseHooks::onNamespaceIsMovable';
$wgHooks['NewRevisionFromEditComplete'][]			= 'WikibaseHooks::onNewRevisionFromEditComplete';



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

$wgResourceModules['wikibase.utilities.jQuery'] = $moduleTemplate + array(
	'scripts' => array(
		'wikibase.utilities/wikibase.utilities.js',
		'wikibase.utilities/wikibase.utilities.jQuery.js',
		'wikibase.utilities/wikibase.utilities.jQuery.ui.js',
		'wikibase.utilities/wikibase.utilities.jQuery.ui.inputAutoExpand.js',
		'wikibase.utilities/wikibase.utilities.jQuery.ui.wikibaseAutocomplete.js'
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
		'wikibase.utilities.jQuery',
		'jquery.ui.autocomplete',
		'mediawiki.api',
		'mediawiki.Title',
		'mediawiki.jqueryMsg' // for {{plural}} and {{gender}} support in messages
	),
	'messages' => array(
		'wikibase-sitelinks',
		'wikibase-cancel',
		'wikibase-edit',
		'wikibase-save',
		'wikibase-add',
		'wikibase-save-inprogress',
		'wikibase-remove-inprogress',
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
		'wikibase-error-remove-generic',
		'wikibase-error-save-connection',
		'wikibase-error-remove-connection',
		'wikibase-error-save-timeout',
		'wikibase-error-remove-timeout',
		'wikibase-error-autocomplete-connection',
		'wikibase-error-autocomplete-response'
	)
);

unset( $moduleTemplate );

// register hooks and handlers
define( 'CONTENT_MODEL_WIKIBASE_ITEM', 1001 ); //@todo: register at http://mediawiki.org/wiki/ContentHandeler/registry

$wgContentHandlers[CONTENT_MODEL_WIKIBASE_ITEM] = '\Wikibase\ItemHandler';

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

$wgNamespaceContentModels[WB_NS_DATA] = CONTENT_MODEL_WIKIBASE_ITEM;

$egWBSettings = array();
