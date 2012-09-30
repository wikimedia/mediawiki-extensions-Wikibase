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

// Include the Diff extension if that hasn't been done yet, since it's required for WikibaseLib to work.
if ( !defined( 'Diff_VERSION' ) ) {
	@include_once( __DIR__ . '/../../Diff/Diff.php' );
}

// Include the DataValues extension if that hasn't been done yet, since it's required for WikibaseLib to work.
if ( !defined( 'DataValues_VERSION' ) ) {
	@include_once( __DIR__ . '/../../DataValues/DataValues.php' );
}

$dependencies = array(
	'Diff_VERSION' => 'Diff',
	'DataValues_VERSION' => 'DataValues',
	'ValueParsers_VERSION' => 'ValueParsers',
	'DataTypes_VERSION' => 'DataTypes',
);

foreach ( $dependencies as $constant => $name ) {
	if ( !defined( $constant ) ) {
		die(
			'<b>Error:</b> WikibaseLib depends on the <a href="https://www.mediawiki.org/wiki/Extension:'
				. $name . '">' . $name . '</a> extension.'
		);
	}
}

unset( $dependencies );

define( 'WBL_VERSION', '0.2 alpha' );

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

$dir = __DIR__ . '/';

// constants
define( 'CONTENT_MODEL_WIKIBASE_ITEM', "wikibase-item" );
define( 'CONTENT_MODEL_WIKIBASE_PROPERTY', "wikibase-property" );
define( 'CONTENT_MODEL_WIKIBASE_QUERY', "wikibase-query" );

define( 'SUMMARY_MAX_LENGTH', 250 );

// i18n
$wgExtensionMessagesFiles['WikibaseLib'] 			= $dir . 'WikibaseLib.i18n.php';



// Autoloading
$wgAutoloadClasses['Wikibase\LibHooks'] 			= $dir . 'WikibaseLib.hooks.php';

// includes
$wgAutoloadClasses['Wikibase\ChangeHandler'] 		= $dir . 'includes/ChangeHandler.php';
$wgAutoloadClasses['Wikibase\ChangeNotifier'] 		= $dir . 'includes/ChangeNotifier.php';
$wgAutoloadClasses['Wikibase\ChangesTable'] 		= $dir . 'includes/ChangesTable.php';
$wgAutoloadClasses['Wikibase\Comparable'] 			= $dir . 'includes/Comparable.php';
$wgAutoloadClasses['Wikibase\DiffView'] 			= $dir . 'includes/DiffView.php';
$wgAutoloadClasses['Wikibase\Hashable'] 			= $dir . 'includes/Hashable.php';
$wgAutoloadClasses['Wikibase\HashArray'] 			= $dir . 'includes/HashArray.php';
$wgAutoloadClasses['Wikibase\Immutable'] 			= $dir . 'includes/Immutable.php';
$wgAutoloadClasses['Wikibase\MapHasher'] 			= $dir . 'includes/MapHasher.php';
$wgAutoloadClasses['Wikibase\MapValueHasher'] 		= $dir . 'includes/MapValueHasher.php';
$wgAutoloadClasses['Wikibase\Reference'] 			= $dir . 'includes/Reference.php';
$wgAutoloadClasses['Wikibase\ReferenceObject'] 		= $dir . 'includes/ReferenceObject.php';
$wgAutoloadClasses['Wikibase\ObjectComparer'] 		= $dir . 'includes/ObjectComparer.php';
$wgAutoloadClasses['Wikibase\Settings'] 			= $dir . 'includes/Settings.php';
$wgAutoloadClasses['Wikibase\SiteLink'] 			= $dir . 'includes/SiteLink.php';
$wgAutoloadClasses['Wikibase\Statement'] 			= $dir . 'includes/Statement.php';
$wgAutoloadClasses['Wikibase\StatementObject'] 		= $dir . 'includes/StatementObject.php';
$wgAutoloadClasses['Wikibase\Utils'] 				= $dir . 'includes/Utils.php';

// includes/changes
$wgAutoloadClasses['Wikibase\Change'] 				= $dir . 'includes/changes/Change.php';
$wgAutoloadClasses['Wikibase\ChangeRow'] 			= $dir . 'includes/changes/ChangeRow.php';
$wgAutoloadClasses['Wikibase\DiffChange'] 			= $dir . 'includes/changes/DiffChange.php';
$wgAutoloadClasses['Wikibase\EntityCreation'] 		= $dir . 'includes/changes/EntityCreation.php';
$wgAutoloadClasses['Wikibase\EntityDeletion'] 		= $dir . 'includes/changes/EntityDeletion.php';
$wgAutoloadClasses['Wikibase\EntityRefresh'] 		= $dir . 'includes/changes/EntityRefresh.php';
$wgAutoloadClasses['Wikibase\EntityUpdate'] 		= $dir . 'includes/changes/EntityUpdate.php';

// includes/claims
$wgAutoloadClasses['Wikibase\Claim'] 				= $dir . 'includes/claim/Claim.php';
$wgAutoloadClasses['Wikibase\ClaimList'] 			= $dir . 'includes/claim/ClaimList.php';
$wgAutoloadClasses['Wikibase\ClaimObject'] 			= $dir . 'includes/claim/ClaimObject.php';
$wgAutoloadClasses['Wikibase\Claims'] 				= $dir . 'includes/claim/Claims.php';

// includes/entity
$wgAutoloadClasses['Wikibase\Entity'] 				= $dir . 'includes/entity/Entity.php';
$wgAutoloadClasses['Wikibase\EntityDiff'] 			= $dir . 'includes/entity/EntityDiff.php';
$wgAutoloadClasses['Wikibase\EntityDiffObject'] 	= $dir . 'includes/entity/EntityDiffObject.php';
$wgAutoloadClasses['Wikibase\EntityDiffView'] 		= $dir . 'includes/entity/EntityDiffView.php';
$wgAutoloadClasses['Wikibase\EntityObject'] 		= $dir . 'includes/entity/EntityObject.php';

// includes/item
$wgAutoloadClasses['Wikibase\Item'] 				= $dir . 'includes/item/Item.php';
$wgAutoloadClasses['Wikibase\ItemDiff'] 			= $dir . 'includes/item/ItemDiff.php';
$wgAutoloadClasses['Wikibase\ItemDiffView'] 		= $dir . 'includes/item/ItemDiffView.php';
$wgAutoloadClasses['Wikibase\ItemObject'] 			= $dir . 'includes/item/ItemObject.php';

// includes/property
$wgAutoloadClasses['Wikibase\Property'] 			= $dir . 'includes/property/Property.php';
$wgAutoloadClasses['Wikibase\PropertyObject'] 		= $dir . 'includes/property/PropertyObject.php';

// includes/query
$wgAutoloadClasses['Wikibase\Query'] 				= $dir . 'includes/query/Query.php';
$wgAutoloadClasses['Wikibase\QueryObject'] 			= $dir . 'includes/query/QueryObject.php';

// includes/reference
$wgAutoloadClasses['Wikibase\Reference'] 				= $dir . 'includes/reference/Reference.php';
$wgAutoloadClasses['Wikibase\ReferenceList'] 			= $dir . 'includes/reference/ReferenceList.php';
$wgAutoloadClasses['Wikibase\ReferenceObject'] 			= $dir . 'includes/reference/ReferenceObject.php';
$wgAutoloadClasses['Wikibase\References'] 				= $dir . 'includes/reference/References.php';

// includes/snak
$wgAutoloadClasses['Wikibase\InstanceOfSnak'] 			= $dir . 'includes/snak/InstanceOfSnak.php';
$wgAutoloadClasses['Wikibase\PropertyNoValueSnak'] 		= $dir . 'includes/snak/PropertyNoValueSnak.php';
$wgAutoloadClasses['Wikibase\PropertySnak'] 			= $dir . 'includes/snak/PropertySnak.php';
$wgAutoloadClasses['Wikibase\PropertySnakObject'] 		= $dir . 'includes/snak/PropertySnakObject.php';
$wgAutoloadClasses['Wikibase\PropertyValueSnak'] 		= $dir . 'includes/snak/PropertyValueSnak.php';
$wgAutoloadClasses['Wikibase\PropertySomeValueSnak'] 	= $dir . 'includes/snak/PropertySomeValueSnak.php';
$wgAutoloadClasses['Wikibase\Snak'] 					= $dir . 'includes/snak/Snak.php';
$wgAutoloadClasses['Wikibase\SnakList'] 				= $dir . 'includes/snak/SnakList.php';
$wgAutoloadClasses['Wikibase\SnakObject'] 				= $dir . 'includes/snak/SnakObject.php';
$wgAutoloadClasses['Wikibase\Snaks'] 					= $dir . 'includes/snak/Snaks.php';
$wgAutoloadClasses['Wikibase\SubclassOfSnak'] 			= $dir . 'includes/snak/SubclassOfSnak.php';

// includes/store
$wgAutoloadClasses['Wikibase\SiteLinkCache'] 			= $dir . 'includes/store/SiteLinkCache.php';
$wgAutoloadClasses['Wikibase\SiteLinkLookup'] 			= $dir . 'includes/store/SiteLinkLookup.php';
$wgAutoloadClasses['Wikibase\SiteLinkTable'] 			= $dir . 'includes/store/SiteLinkTable.php';

// tests
$wgAutoloadClasses['Wikibase\Test\HashArrayTest'] 			= $dir . 'tests/phpunit/HashArrayTest.php';
$wgAutoloadClasses['Wikibase\Test\TestItems'] 				= $dir . 'tests/phpunit/item/TestItems.php';
$wgAutoloadClasses['Wikibase\Test\EntityObjectTest'] 		= $dir . 'tests/phpunit/entity/EntityObjectTest.php';
$wgAutoloadClasses['Wikibase\Test\EntityRefreshTest'] 		= $dir . 'tests/phpunit/changes/EntityRefreshTest.php';
$wgAutoloadClasses['Wikibase\Test\PropertySnakObjectTest'] 	= $dir . 'tests/phpunit/snak/PropertySnakObjectTest.php';
$wgAutoloadClasses['Wikibase\Test\SnakObjectTest'] 			= $dir . 'tests/phpunit/snak/SnakObjectTest.php';

$wgAutoloadClasses['SiteObjectTest'] 			= $dir . 'tests/phpunit/site/SiteObjectTest.php';



foreach ( array(
			  'Settings',
			  'SettingsBase'
		  ) as $compatClass ) {
	if ( !array_key_exists( $compatClass, $wgAutoloadLocalClasses ) ) {
		$wgAutoloadClasses[$compatClass] = $dir . 'compat/' . $compatClass . '.php';
	}
}


$wgDataTypes['wikibase-item'] = array(
	'datavalue' => 'number',
);


// Hooks
$wgHooks['WikibaseDefaultSettings'][]				= 'Wikibase\LibHooks::onWikibaseDefaultSettings';
$wgHooks['LoadExtensionSchemaUpdates'][] 			= 'Wikibase\LibHooks::onSchemaUpdate';
$wgHooks['UnitTestsList'][]							= 'Wikibase\LibHooks::registerUnitTests';


$egWBDefaultsFunction = null;

$egWBSettings = array();




$wgSiteTypes = array();
$wgSiteTypes['mediawiki'] = 'MediaWikiSite';

$wgAutoloadClasses['TestSites'] 			= $dir . 'tests/phpunit/site/TestSites.php';

$wgAutoloadClasses['Site'] 					= $dir . 'includes/site/Site.php';
$wgAutoloadClasses['SiteArray'] 			= $dir . 'includes/site/SiteArray.php';
$wgAutoloadClasses['SiteList'] 				= $dir . 'includes/site/SiteList.php';
$wgAutoloadClasses['SiteObject'] 			= $dir . 'includes/site/SiteObject.php';
$wgAutoloadClasses['SitePaths'] 			= $dir . 'includes/site/SitePaths.php';
$wgAutoloadClasses['SitePathsObject'] 		= $dir . 'includes/site/SitePathsObject.php';
$wgAutoloadClasses['Sites'] 				= $dir . 'includes/site/Sites.php';
$wgAutoloadClasses['SitesTable'] 			= $dir . 'includes/site/SitesTable.php';
$wgAutoloadClasses['MediaWikiSite'] 		= $dir . 'includes/site/MediaWikiSite.php';
