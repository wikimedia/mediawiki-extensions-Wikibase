<?php

/**
 * Initialization file for the WikibaseLib extension.
 *
 * Documentation:	 		https://www.mediawiki.org/wiki/Extension:WikibaseLib
 * Support					https://www.mediawiki.org/wiki/Extension_talk:WikibaseLib
 * Source code:				https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/extensions/WikibaseLib.git
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

/**
 * This documentation group collects source code files belonging to WikibaseLib.
 *
 * @defgroup WikibaseLib WikibaseLib
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

if ( version_compare( $wgVersion, '1.20c', '<' ) ) { // Needs to be 1.20c because version_compare() works in confusing ways.
	die( '<b>Error:</b> WikibaseLib requires MediaWiki 1.20 or above.' );
}

define( 'WBL_VERSION', '0.1 alpha' );

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'WikibaseLib',
	'version' => WBL_VERSION,
	'author' => array(
		'The Wikidata team', // TODO: link?
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:WikibaseLib',
	'descriptionmsg' => 'wikibaselib-desc'
);

$dir = dirname( __FILE__ ) . '/';



// i18n
$wgExtensionMessagesFiles['WikibaseLib'] 			= $dir . 'WikibaseLib.i18n.php';



// Autoloading
$wgAutoloadClasses['WikibaseLibHooks'] 				= $dir . 'WikibaseLib.hooks.php';

// includes
$wgAutoloadClasses['WikibaseChanges'] 				= $dir . 'includes/WikibaseChanges.php';
$wgAutoloadClasses['WikibaseListDiff'] 				= $dir . 'includes/WikibaseListDiff.php';
$wgAutoloadClasses['WikibaseMapDiff'] 				= $dir . 'includes/WikibaseMapDiff.php';

// includes/changes
$wgAutoloadClasses['WikibaseAliasChange'] 			= $dir . 'includes/changes/WikibaseAliasChange.php';
$wgAutoloadClasses['WikibaseChange'] 				= $dir . 'includes/changes/WikibaseChange.php';
$wgAutoloadClasses['WikibaseListChange'] 			= $dir . 'includes/changes/WikibaseListChange.php';
$wgAutoloadClasses['WikibaseMapChange'] 			= $dir . 'includes/changes/WikibaseMapChange.php';
$wgAutoloadClasses['WikibaseSitelinkChange'] 		= $dir . 'includes/changes/WikibaseSitelinkChange.php';

// tests
$wgAutoloadClasses['WikibaseChangesTest'] 			= $dir . 'tests/phpunit/WikibaseSitelinkChange.php';
$wgAutoloadClasses['WikibaseListDiffTest'] 			= $dir . 'tests/phpunit/WikibaseListDiffTest.php';
$wgAutoloadClasses['WikibaseMapDiffTest'] 			= $dir . 'tests/phpunit/WikibaseMapDiffTest.php';



// Hooks
$wgHooks['LoadExtensionSchemaUpdates'][] 			= 'WikibaseLibHooks::onSchemaUpdate';
$wgHooks['UnitTestsList'][]							= 'WikibaseLibHooks::registerUnitTests';



$wgSharedTables[] = 'wb_changes';