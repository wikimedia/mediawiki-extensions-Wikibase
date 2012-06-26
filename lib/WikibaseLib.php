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

// constants
define( 'CONTENT_MODEL_WIKIBASE_ITEM', 1001 ); //@todo: register at http://mediawiki.org/wiki/ContentHandeler/registry

define( 'SITE_TYPE_MEDIAWIKI', 0 );
define( 'SITE_TYPE_UNKNOWN', 1 );

define( 'SITE_GROUP_NONE', -1 );
define( 'SITE_GROUP_WIKIPEDIA', 0 );
define( 'SITE_GROUP_WIKTIONARY', 1 );
define( 'SITE_GROUP_WIKIBOOKS', 2 );
define( 'SITE_GROUP_WIKIQUOTE', 3 );
define( 'SITE_GROUP_WIKISOURCE', 4 );
define( 'SITE_GROUP_WIKIVERSITY', 5 );
define( 'SITE_GROUP_WIKINEWS', 6 );



$wgSiteTypes = array();

$wgSiteTypes[SITE_TYPE_MEDIAWIKI] = 'Wikibase\MediaWikiSite';

// i18n
$wgExtensionMessagesFiles['WikibaseLib'] 			= $dir . 'WikibaseLib.i18n.php';



// Autoloading
$wgAutoloadClasses['Wikibase\LibHooks'] 			= $dir . 'WikibaseLib.hooks.php';

// includes
$wgAutoloadClasses['Wikibase\Changes'] 				= $dir . 'includes/Changes.php';
$wgAutoloadClasses['Wikibase\ChangeHandler'] 		= $dir . 'includes/ChangeHandler.php';
$wgAutoloadClasses['Wikibase\Entity'] 				= $dir . 'includes/Entity.php';
$wgAutoloadClasses['Wikibase\EntityDiff'] 			= $dir . 'includes/EntityDiff.php';
$wgAutoloadClasses['Wikibase\Item'] 				= $dir . 'includes/Item.php';
$wgAutoloadClasses['Wikibase\ItemDiff'] 			= $dir . 'includes/ItemDiff.php';
$wgAutoloadClasses['Wikibase\MediaWikiSite'] 		= $dir . 'includes/MediaWikiSite.php';
$wgAutoloadClasses['Wikibase\Settings'] 			= $dir . 'includes/Settings.php';
$wgAutoloadClasses['Wikibase\Site'] 				= $dir . 'includes/Site.php';
$wgAutoloadClasses['Wikibase\SiteConfig'] 			= $dir . 'includes/SiteConfig.php';
$wgAutoloadClasses['Wikibase\SiteConfigObject'] 	= $dir . 'includes/SiteConfigObject.php';
$wgAutoloadClasses['Wikibase\SiteList'] 			= $dir . 'includes/SiteList.php';
$wgAutoloadClasses['Wikibase\SiteRow'] 				= $dir . 'includes/SiteRow.php';
$wgAutoloadClasses['Wikibase\Sites'] 				= $dir . 'includes/Sites.php';
$wgAutoloadClasses['Wikibase\SitesTable'] 			= $dir . 'includes/SitesTable.php';
$wgAutoloadClasses['Wikibase\Utils'] 				= $dir . 'includes/Utils.php';

// includes/changes
$wgAutoloadClasses['Wikibase\Change'] 				= $dir . 'includes/changes/Change.php';
$wgAutoloadClasses['Wikibase\DiffChange'] 			= $dir . 'includes/changes/DiffChange.php';
$wgAutoloadClasses['Wikibase\ItemChange'] 			= $dir . 'includes/changes/ItemChange.php';

// includes/diff
$wgAutoloadClasses['Wikibase\DiffOp'] 				= $dir . 'includes/diff/DiffOp.php';
$wgAutoloadClasses['Wikibase\IDiff'] 				= $dir . 'includes/diff/IDiff.php';
$wgAutoloadClasses['Wikibase\IDiffOp'] 				= $dir . 'includes/diff/DiffOp.php';
$wgAutoloadClasses['Wikibase\DiffOpAdd'] 			= $dir . 'includes/diff/DiffOpAdd.php';
$wgAutoloadClasses['Wikibase\DiffOpChange'] 		= $dir . 'includes/diff/DiffOpChange.php';
$wgAutoloadClasses['Wikibase\Diff'] 				= $dir . 'includes/diff/Diff.php';
$wgAutoloadClasses['Wikibase\DiffOpRemove'] 		= $dir . 'includes/diff/DiffOpRemove.php';
$wgAutoloadClasses['Wikibase\ListDiff'] 			= $dir . 'includes/diff/ListDiff.php';
$wgAutoloadClasses['Wikibase\MapDiff'] 				= $dir . 'includes/diff/MapDiff.php';

// tests
$wgAutoloadClasses['Wikibase\Test\TestItems'] 				= $dir . 'tests/phpunit/TestItems.php';

// tests/changes
$wgAutoloadClasses['Wikibase\tests\AliasChangeTest'] 		= $dir . 'tests/phpunit/changes/AliasChangeTest.php';
$wgAutoloadClasses['Wikibase\tests\SitelinkChangeTest'] 	= $dir . 'tests/phpunit/changes/SitelinkChangeTest.php';

// tests/diff
$wgAutoloadClasses['Wikibase\tests\ListDiffTest'] 			= $dir . 'tests/phpunit/diff/ListDiffTest.php';
$wgAutoloadClasses['Wikibase\tests\MapDiffTest'] 			= $dir . 'tests/phpunit/diff/MapDiffTest.php';



// Hooks
$wgHooks['LoadExtensionSchemaUpdates'][] 			= 'Wikibase\LibHooks::onSchemaUpdate';
$wgHooks['UnitTestsList'][]							= 'Wikibase\LibHooks::registerUnitTests';



$wgSharedTables[] = 'wb_changes';



$egWBDefaultsFunction = null;

$egWBSettings = array();
