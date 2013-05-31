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

define( 'WBC_VERSION', '0.4 alpha'
	. ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ? '/experimental' : '' ) );

$wgExtensionCredits['wikibase'][] = array(
	'path' => __DIR__,
	'name' => 'Wikibase Client',
	'version' => WBC_VERSION,
	'author' => array(
		'The Wikidata team', // TODO: link?
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:Wikibase_Client',
	'descriptionmsg' => 'wikibase-client-desc'
);

$dir = __DIR__ . '/';

// i18n
$wgExtensionMessagesFiles['wikibaseclient'] 		= $dir . 'WikibaseClient.i18n.php';
$wgExtensionMessagesFiles['Wikibaseclientalias']	= $dir . 'WikibaseClient.i18n.alias.php';
$wgExtensionMessagesFiles['wikibaseclientmagic']	= $dir . 'WikibaseClient.i18n.magic.php';

// Autoloading
$wgAutoloadClasses['Wikibase\ClientHooks'] 			= $dir . 'WikibaseClient.hooks.php';

$wgAutoloadClasses['Wikibase\CachedEntity'] 		= $dir . 'includes/CachedEntity.php';
$wgAutoloadClasses['Wikibase\EntityCacheUpdater'] 	= $dir . 'includes/EntityCacheUpdater.php';
$wgAutoloadClasses['Wikibase\EntityIdPropertyUpdater'] = $dir . 'includes/EntityIdPropertyUpdater.php';
$wgAutoloadClasses['Wikibase\InterwikiSorter']      = $dir . 'includes/InterwikiSorter.php';
$wgAutoloadClasses['Wikibase\LangLinkHandler'] 		= $dir . 'includes/LangLinkHandler.php';
$wgAutoloadClasses['Wikibase\ChangeHandler'] 			= $dir . 'includes/ChangeHandler.php';
$wgAutoloadClasses['Wikibase\NamespaceChecker']		= $dir . 'includes/NamespaceChecker.php';
$wgAutoloadClasses['Wikibase\RepoItemLinkGenerator']	= $dir . 'includes/RepoItemLinkGenerator.php';
$wgAutoloadClasses['Wikibase\RepoLinker']			= $dir . 'includes/RepoLinker.php';
$wgAutoloadClasses['Wikibase\Client\WikibaseClient'] = $dir . 'includes/WikibaseClient.php';
$wgAutoloadClasses['Scribunto_LuaWikibaseLibrary']      = $dir . 'includes/WikibaseLibrary.php';
$wgAutoloadClasses['Wikibase\PageUpdater'] 	= $dir . 'includes/PageUpdater.php';
$wgAutoloadClasses['Wikibase\WikiPageUpdater'] 	= $dir . 'includes/WikiPageUpdater.php';

// includes/api
$wgAutoloadClasses['Wikibase\ApiClientInfo']		= $dir . 'includes/api/ApiClientInfo.php';

// includes/modules
$wgAutoloadClasses['Wikibase\SiteModule']  = $dir . 'includes/modules/SiteModule.php';

// include/parserhooks
$wgAutoloadClasses['Wikibase\NoLangLinkHandler']    = $dir . 'includes/parserhooks/NoLangLinkHandler.php';
$wgAutoloadClasses['Wikibase\ParserErrorMessageFormatter']	= $dir . 'includes/parserhooks/ParserErrorMessageFormatter.php';
$wgAutoloadClasses['Wikibase\PropertyParserFunction'] = $dir . 'includes/parserhooks/PropertyParserFunction.php';

// includes/recentchanges
$wgAutoloadClasses['Wikibase\ExternalChangesLine']	= $dir . 'includes/recentchanges/ExternalChangesLine.php';
$wgAutoloadClasses['Wikibase\ExternalRecentChange'] = $dir . 'includes/recentchanges/ExternalRecentChange.php';
$wgAutoloadClasses['Wikibase\RecentChangesFilterOptions'] 	= $dir . 'includes/recentchanges/RecentChangesFilterOptions.php';

// includes/specials
$wgAutoloadClasses['SpecialUnconnectedPages']			= $dir . 'includes/specials/SpecialUnconnectedPages.php';

// includes/store
$wgAutoloadClasses['Wikibase\ClientStore'] 			= $dir . 'includes/store/ClientStore.php';
$wgAutoloadClasses['Wikibase\EntityCache'] 			= $dir . 'includes/store/EntityCache.php';

// includes/store/sql
$wgAutoloadClasses['Wikibase\CachingSqlStore'] 		= $dir . 'includes/store/sql/CachingSqlStore.php';
$wgAutoloadClasses['Wikibase\DirectSqlStore'] 		= $dir . 'includes/store/sql/DirectSqlStore.php';
$wgAutoloadClasses['Wikibase\EntityCacheTable'] 	= $dir . 'includes/store/sql/EntityCacheTable.php';

// test
$wgAutoloadClasses['Wikibase\Test\MockPageUpdater'] 	= $dir . 'tests/phpunit/MockPageUpdater.php';

// Hooks
$wgHooks['UnitTestsList'][] 				= '\Wikibase\ClientHooks::registerUnitTests';
$wgHooks['LoadExtensionSchemaUpdates'][] 		= '\Wikibase\ClientHooks::onSchemaUpdate';
$wgHooks['OldChangesListRecentChangesLine'][]		= '\Wikibase\ClientHooks::onOldChangesListRecentChangesLine';
$wgHooks['OutputPageParserOutput'][]		= '\Wikibase\ClientHooks::onOutputPageParserOutput';
$wgHooks['ParserAfterParse'][]				= '\Wikibase\ClientHooks::onParserAfterParse';
$wgHooks['ParserFirstCallInit'][]			= '\Wikibase\ClientHooks::onParserFirstCallInit';
$wgHooks['MagicWordwgVariableIDs'][]			= '\Wikibase\ClientHooks::onMagicWordwgVariableIDs';
$wgHooks['ParserGetVariableValueSwitch'][]		= '\Wikibase\ClientHooks::onParserGetVariableValueSwitch';
$wgHooks['SkinTemplateOutputPageBeforeExec'][]		= '\Wikibase\ClientHooks::onSkinTemplateOutputPageBeforeExec';
$wgHooks['SpecialMovepageAfterMove'][]				= '\Wikibase\ClientHooks::onSpecialMovepageAfterMove';
$wgHooks['SpecialWatchlistQuery'][]			= '\Wikibase\ClientHooks::onSpecialWatchlistQuery';
$wgHooks['SpecialRecentChangesQuery'][]				= '\Wikibase\ClientHooks::onSpecialRecentChangesQuery';
$wgHooks['SpecialRecentChangesFilters'][]			= '\Wikibase\ClientHooks::onSpecialRecentChangesFilters';
$wgHooks['GetPreferences'][]						= '\Wikibase\ClientHooks::onGetPreferences';
$wgHooks['BeforePageDisplay'][]				= '\Wikibase\ClientHooks::onBeforePageDisplay';
$wgHooks['ScribuntoExternalLibraries'][]      = '\Wikibase\ClientHooks::onScribuntoExternalLibraries';
$wgHooks['SpecialWatchlistFilters'][]          = '\Wikibase\ClientHooks::onSpecialWatchlistFilters';

// extension hooks
$wgHooks['WikibaseDeleteData'][]			            = '\Wikibase\ClientHooks::onWikibaseDeleteData';
$wgHooks['WikibaseRebuildData'][]			            = '\Wikibase\ClientHooks::onWikibaseRebuildData';
$wgHooks['InfoAction'][] 								= '\Wikibase\ClientHooks::onInfoAction';

// api modules
$wgAPIMetaModules['wikibase'] = 'Wikibase\ApiClientInfo';

// Special page registration
$wgSpecialPages['UnconnectedPages']						= 'SpecialUnconnectedPages';

// Special page groups
$wgSpecialPageGroups['UnconnectedPages']				= 'wikibaseclient';

// Resource loader modules
$wgResourceModules = array_merge( $wgResourceModules, include( "$dir/resources/Resources.php" ) );

$wgWBClientStores = array();
$wgWBClientStores['CachingSqlStore'] = 'Wikibase\CachingSqlStore';
$wgWBClientStores['DirectSqlStore'] = 'Wikibase\DirectSqlStore';

$wgWBClientSettings = array_merge(
	require( __DIR__ . '/../lib/config/WikibaseLib.default.php' ),
	require( __DIR__ . '/config/WikibaseClient.default.php' )
);

$wgWBSettings = &$wgWBClientSettings; // B/C

if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
	include_once( $dir . 'config/WikibaseClient.experimental.php' );
}
