<?php
/**
 * Initialization file for the Wikibase Client extension.
 *
 * Documentation:	 		https://www.mediawiki.org/wiki/Extension:Wikibase_Client
 * Support					https://www.mediawiki.org/wiki/Extension_talk:Wikibase_Client
 * Source code:				https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/extensions/Wikibase.git;a=tree;f=client
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 */

/**
 * This documentation group collects source code files belonging to Wikibase Client.
 *
 * @defgroup WikibaseClient Wikibase Client
 * @ingroup Wikibase
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( "Not an entry point.\n" );
}

if ( version_compare( $wgVersion, '1.21c', '<' ) ) { // Needs to be 1.21c because version_compare() works in confusing ways.
	die( "<b>Error:</b> Wikibase requires MediaWiki 1.21 alpha or above.\n" );
}

// Include the WikibaseLib extension if that hasn't been done yet, since it's required for WikibaseClient to work.
if ( !defined( 'WBL_VERSION' ) ) {
	@include_once( __DIR__ . '/../lib/WikibaseLib.php' );
}

if ( !defined( 'WBL_VERSION' ) ) {
	die( '<b>Error:</b> WikibaseClient depends on the <a href="https://www.mediawiki.org/wiki/Extension:WikibaseLib">WikibaseLib</a> extension.' );
}

define( 'WBC_VERSION', '0.4 alpha' );

$wgExtensionCredits['other'][] = array(
	'path' => __DIR__,
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
$wgAutoloadClasses['Wikibase\ClientHooks'] 			= $dir . 'WikibaseClient.hooks.php';

$wgAutoloadClasses['Wikibase\CachedEntity'] 		= $dir . 'includes/CachedEntity.php';
$wgAutoloadClasses['Wikibase\ClientUtils']		= $dir . 'includes/ClientUtils.php';
$wgAutoloadClasses['Wikibase\EntityCacheUpdater'] 	= $dir . 'includes/EntityCacheUpdater.php';
$wgAutoloadClasses['Wikibase\LangLinkHandler'] 		= $dir . 'includes/LangLinkHandler.php';
$wgAutoloadClasses['Wikibase\NoLangLinkHandler'] 	= $dir . 'includes/NoLangLinkHandler.php';
$wgAutoloadClasses['Wikibase\SortUtils']		= $dir . 'includes/SortUtils.php';

// includes/api
$wgAutoloadClasses['Wikibase\ApiClientInfo']		= $dir . 'includes/api/ApiClientInfo.php';

// includes/handlers
$wgAutoloadClasses['Wikibase\ClientChangeHandler']  = $dir . 'includes/ClientChangeHandler.php';

// includes/recentchanges
$wgAutoloadClasses['Wikibase\ExternalChangesLine']	= $dir . 'includes/recentchanges/ExternalChangesLine.php';
$wgAutoloadClasses['Wikibase\ExternalRecentChange'] = $dir . 'includes/recentchanges/ExternalRecentChange.php';
$wgAutoloadClasses['Wikibase\RecentChangesFilterOptions'] 	= $dir . 'includes/recentchanges/RecentChangesFilterOptions.php';

// includes/store
$wgAutoloadClasses['Wikibase\ClientStore'] 			= $dir . 'includes/store/ClientStore.php';
$wgAutoloadClasses['Wikibase\ClientStoreFactory'] 	= $dir . 'includes/store/ClientStoreFactory.php';
$wgAutoloadClasses['Wikibase\EntityCache'] 			= $dir . 'includes/store/EntityCache.php';

// includes/store/sql
$wgAutoloadClasses['Wikibase\CachingSqlStore'] 		= $dir . 'includes/store/sql/CachingSqlStore.php';
$wgAutoloadClasses['Wikibase\DirectSqlStore'] 		= $dir . 'includes/store/sql/DirectSqlStore.php';
$wgAutoloadClasses['Wikibase\EntityCacheTable'] 	= $dir . 'includes/store/sql/EntityCacheTable.php';

// tests/phpunit
$wgAutoloadClasses['Wikibase\Test\MockRepository'] 		= $dir . 'tests/phpunit/MockRepository.php';

// Hooks
$wgHooks['UnitTestsList'][] 				= '\Wikibase\ClientHooks::registerUnitTests';
$wgHooks['LoadExtensionSchemaUpdates'][] 		= '\Wikibase\ClientHooks::onSchemaUpdate';
$wgHooks['OldChangesListRecentChangesLine'][]		= '\Wikibase\ClientHooks::onOldChangesListRecentChangesLine';
$wgHooks['ParserAfterParse'][]				= '\Wikibase\ClientHooks::onParserAfterParse';
$wgHooks['ParserFirstCallInit'][]			= '\Wikibase\NoLangLinkHandler::onParserFirstCallInit';
$wgHooks['MagicWordwgVariableIDs'][]			= '\Wikibase\NoLangLinkHandler::onMagicWordwgVariableIDs';
$wgHooks['ParserGetVariableValueSwitch'][]		= '\Wikibase\NoLangLinkHandler::onParserGetVariableValueSwitch';
$wgHooks['ResourceLoaderTestModules'][]             = 'Wikibase\ClientHooks::onRegisterQUnitTests';
$wgHooks['SkinTemplateOutputPageBeforeExec'][]		= '\Wikibase\ClientHooks::onSkinTemplateOutputPageBeforeExec';
$wgHooks['SpecialMovepageAfterMove'][]				= '\Wikibase\ClientHooks::onSpecialMovepageAfterMove';
$wgHooks['SpecialWatchlistQuery'][]			= '\Wikibase\ClientHooks::onSpecialWatchlistQuery';
$wgHooks['SpecialRecentChangesQuery'][]				= '\Wikibase\ClientHooks::onSpecialRecentChangesQuery';
$wgHooks['SpecialRecentChangesFilters'][]			= '\Wikibase\ClientHooks::onSpecialRecentChangesFilters';
$wgHooks['GetPreferences'][]						= '\Wikibase\ClientHooks::onGetPreferences';
$wgHooks['BeforePageDisplay'][]				= '\Wikibase\ClientHooks::onBeforePageDisplay';
$wgHooks['SpecialPageBeforeExecute'][]		= '\Wikibase\ClientHooks::onSpecialPageBeforeExecute';

// extension hooks
$wgHooks['WikibasePollHandle'][]                        = '\Wikibase\ClientHooks::onWikibasePollHandle';
$wgHooks['WikibaseDefaultSettings'][]                   = '\Wikibase\ClientHooks::onWikibaseDefaultSettings';
$wgHooks['WikibaseDeleteData'][]			            = '\Wikibase\ClientHooks::onWikibaseDeleteData';
$wgHooks['WikibaseRebuildData'][]			            = '\Wikibase\ClientHooks::onWikibaseRebuildData';

// api modules
$wgAPIMetaModules['wikibase'] = 'Wikibase\ApiClientInfo';

// Resource loader modules
$wgResourceModules = array_merge( $wgResourceModules, include( "$dir/resources/Resources.php" ) );

$wgWBClientStores = array();
$wgWBClientStores['CachingSqlStore'] = 'Wikibase\CachingSqlStore';
$wgWBClientStores['DirectSqlStore'] = 'Wikibase\DirectSqlStore';
