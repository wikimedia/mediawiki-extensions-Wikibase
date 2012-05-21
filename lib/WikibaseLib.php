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
$wgAutoloadClasses['WBLSettings'] 					= $dir . 'WikibaseLib.settings.php';

// includes
$wgAutoloadClasses['Wikibase\Changes'] 				= $dir . 'includes/Changes.php';

// includes/changes
$wgAutoloadClasses['Wikibase\AliasChange'] 			= $dir . 'includes/changes/AliasChange.php';
$wgAutoloadClasses['Wikibase\Change'] 				= $dir . 'includes/changes/Change.php';
$wgAutoloadClasses['Wikibase\ListChange'] 			= $dir . 'includes/changes/ListChange.php';
$wgAutoloadClasses['Wikibase\MapChange'] 			= $dir . 'includes/changes/MapChange.php';
$wgAutoloadClasses['Wikibase\SitelinkChange'] 		= $dir . 'includes/changes/SitelinkChange.php';

// includes/diff
$wgAutoloadClasses['Wikibase\DiffOp'] 				= $dir . 'includes/diff/DiffOp.php';
$wgAutoloadClasses['Wikibase\IDiffOp'] 				= $dir . 'includes/diff/DiffOp.php';
$wgAutoloadClasses['Wikibase\DiffOpAdd'] 			= $dir . 'includes/diff/DiffOpAdd.php';
$wgAutoloadClasses['Wikibase\DiffOpChange'] 		= $dir . 'includes/diff/DiffOpChange.php';
$wgAutoloadClasses['Wikibase\Diff'] 				= $dir . 'includes/diff/Diff.php';
$wgAutoloadClasses['Wikibase\IDiff'] 				= $dir . 'includes/diff/Diff.php';
$wgAutoloadClasses['Wikibase\DiffOpRemove'] 		= $dir . 'includes/diff/DiffOpRemove.php';
$wgAutoloadClasses['Wikibase\ListDiff'] 			= $dir . 'includes/diff/ListDiff.php';
$wgAutoloadClasses['Wikibase\MapDiff'] 				= $dir . 'includes/diff/MapDiff.php';

// tests
$wgAutoloadClasses['Wikibase\tests\ChangesTest'] 			= $dir . 'tests/phpunit/ChangesTest.php';

// tests/changes
$wgAutoloadClasses['Wikibase\tests\AliasChangeTest'] 		= $dir . 'tests/phpunit/changes/AliasChangeTest.php';
$wgAutoloadClasses['Wikibase\tests\SitelinkChangeTest'] 	= $dir . 'tests/phpunit/changes/SitelinkChangeTest.php';

// tests/diff
$wgAutoloadClasses['Wikibase\tests\ListDiffTest'] 			= $dir . 'tests/phpunit/diff/ListDiffTest.php';
$wgAutoloadClasses['Wikibase\tests\MapDiffTest'] 			= $dir . 'tests/phpunit/diff/MapDiffTest.php';



// Hooks
$wgHooks['LoadExtensionSchemaUpdates'][] 			= 'WikibaseLibHooks::onSchemaUpdate';
$wgHooks['UnitTestsList'][]							= 'WikibaseLibHooks::registerUnitTests';



$wgSharedTables[] = 'wb_changes';



$egWBLSettings = array();
