<?php
/**
 * Initialization file for the Wikibase Client extension.
 *
 * Documentation:	 		https://www.mediawiki.org/wiki/Extension:Wikibase_Client
 * Support					https://www.mediawiki.org/wiki/Extension_talk:Wikibase_Client
 * Source code:				https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/extensions/Wikibase.git;a=tree;f=client
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 */

/**
 * This documentation group collects source code files belonging to Wikibase Client.
 *
 * @defgroup WikibaseClient Wikibase Client
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( "Not an entry point.\n" );
}

if ( version_compare( $wgVersion, '1.20c', '<' ) ) { // Needs to be 1.20c because version_compare() works in confusing ways.
	die( "<b>Error:</b> Wikibase requires MediaWiki 1.20 or above.\n" );
}

define( 'WBC_VERSION', '0.1 alpha' );

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Wikibase Client',
	'version' => WBC_VERSION,
	'author' => array(
		'The Wikidata team', // TODO: link?
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:Wikibase_Client',
	'descriptionmsg' => 'wbc-desc'
);

$dir = __DIR__ . '/';

// i18n
$wgExtensionMessagesFiles['wikibaseclient'] 		= $dir . 'WikibaseClient.i18n.php';
$wgExtensionMessagesFiles['wikibaseclientmagic']	= $dir . 'WikibaseClient.i18n.magic.php';

// Autoloading
$wgAutoloadClasses['Wikibase\ClientHooks'] 		= $dir . 'WikibaseClient.hooks.php';

$wgAutoloadClasses['Wikibase\CachedEntity'] 		= $dir . 'includes/CachedEntity.php';
$wgAutoloadClasses['Wikibase\EntityCache'] 		= $dir . 'includes/EntityCache.php';
$wgAutoloadClasses['Wikibase\EntityCacheUpdater'] 	= $dir . 'includes/EntityCacheUpdater.php';
$wgAutoloadClasses['Wikibase\LangLinkHandler'] 		= $dir . 'includes/LangLinkHandler.php';
$wgAutoloadClasses['Wikibase\NoLangLinkHandler'] 	= $dir . 'includes/NoLangLinkHandler.php';
$wgAutoloadClasses['Wikibase\SiteLinkCache'] 		= $dir . 'includes/SiteLinkCache.php';
$wgAutoloadClasses['Wikibase\SortUtils']                = $dir . 'includes/SortUtils.php';

// Hooks
$wgHooks['UnitTestsList'][] 				= '\Wikibase\ClientHooks::registerUnitTests';
$wgHooks['WikibasePollHandle'][]			= '\Wikibase\ClientHooks::onWikibasePollHandle';
$wgHooks['LoadExtensionSchemaUpdates'][] 		= '\Wikibase\ClientHooks::onSchemaUpdate';
$wgHooks['WikibaseDefaultSettings'][]			= '\Wikibase\ClientHooks::onWikibaseDefaultSettings';
$wgHooks['ParserAfterParse'][]				= '\Wikibase\ClientHooks::onParserAfterParse';
$wgHooks['ParserFirstCallInit'][]			= '\Wikibase\NoLangLinkHandler::onParserFirstCallInit';
$wgHooks['MagicWordwgVariableIDs'][]			= '\Wikibase\NoLangLinkHandler::onMagicWordwgVariableIDs';
$wgHooks['ParserGetVariableValueSwitch'][]		= '\Wikibase\NoLangLinkHandler::onParserGetVariableValueSwitch';
$wgHooks['SkinTemplateOutputPageBeforeExec'][]		= '\Wikibase\ClientHooks::onSkinTemplateOutputPageBeforeExec';
$wgHooks['BeforePageDisplay'][]				= '\Wikibase\ClientHooks::onBeforePageDisplay';


$wgSharedTables[] = 'wbc_entity_cache';
$wgSharedTables[] = 'wbc_items_per_site';


// Resource loader modules
$moduleTemplate = array(
	'localBasePath' => __DIR__ . '/resources',
	'remoteExtPath' => 'Wikibase/client/resources',
);

$wgResourceModules['ext.wikibaseclient'] = $moduleTemplate + array(
	'styles' => array(
		'ext.wikibaseclient.css'
	),
);

unset( $moduleTemplate );

