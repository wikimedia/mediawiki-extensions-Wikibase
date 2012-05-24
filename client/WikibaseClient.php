<?php

/**
 * Initialization file for the Wikibase Client extension.
 *
 * Documentation:	 		https://www.mediawiki.org/wiki/Extension:Wikibase_Client
 * Support					https://www.mediawiki.org/wiki/Extension_talk:Wikibase_Client
 * Source code:				https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/extensions/WikidataClient.git
 *
 * @file WikibaseClient.php
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

$dir = dirname( __FILE__ ) . '/';

// i18n
$wgExtensionMessagesFiles['wikibaseclient'] 		= $dir . 'WikibaseClient.i18n.php';
$wgExtensionMessagesFiles['wikibaseclientmagic']	= $dir . 'WikibaseClient.i18n.magic.php';

// Autoloading
$wgAutoloadClasses['WBCHooks'] 						= $dir . 'WikibaseClient.hooks.php';
$wgAutoloadClasses['WBCSettings'] 					= $dir . 'WikibaseClient.settings.php';
$wgAutoloadClasses['WBCLangLinkHandler'] 			= $dir . 'includes/WBCLangLinkHandler.php';
$wgAutoloadClasses['WBCNoLangLinkHandler'] 			= $dir . 'includes/WBCNoLangLinkHandler.php';
$wgAutoloadClasses['WBCSkinHandler'] 				= $dir . 'includes/WBCSkinHandler.php';

// Hooks
$wgHooks['ParserBeforeTidy'][] 						= 'WBCLangLinkHandler::onParserBeforeTidy';
$wgHooks['ParserFirstCallInit'][]					= 'WBCNoLangLinkHandler::onParserFirstCallInit';
$wgHooks['MagicWordwgVariableIDs'][]				= 'WBCNoLangLinkHandler::onMagicWordwgVariableIDs';
$wgHooks['ParserGetVariableValueSwitch'][]			= 'WBCNoLangLinkHandler::onParserGetVariableValueSwitch';
$wgHooks['UnitTestsList'][] 						= 'WBCHooks::registerUnitTests';
$wgHooks['SkinTemplateOutputPageBeforeExec'][]		= 'WBCSkinHandler::onSkinTemplateOutputPageBeforeExec';
$wgHooks['BeforePageDisplay'][]						= 'WBCSkinHandler::onBeforePageDisplay';

// Resource loader modules
$moduleTemplate = array(
	'localBasePath' => dirname( __FILE__ ) . '/resources',
	'remoteExtPath' => 'WikidataClient/resources',
);

$wgResourceModules['ext.wikibaseclient'] = $moduleTemplate + array(
	'styles' => array(
		'ext.wikibaseclient.css'
	),
);

unset( $moduleTemplate );

$egWBCSettings = array();

