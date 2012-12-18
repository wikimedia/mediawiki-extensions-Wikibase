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
 * This documentation group collects source code files belonging to Wikibase.
 *
 * @defgroup Wikibase Wikibase
 */

/**
 * This documentation group collects source code files belonging to WikibaseLib.
 *
 * @defgroup WikibaseLib WikibaseLib
 * @ingroup Wikibase
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

define( 'WBL_VERSION', '0.4 alpha' );

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

define( 'SUMMARY_MAX_LENGTH', 250 );

// i18n
$wgExtensionMessagesFiles['WikibaseLib'] 			= $dir . 'WikibaseLib.i18n.php';



// Autoloading
$wgAutoloadClasses['Wikibase\LibHooks'] 			= $dir . 'WikibaseLib.hooks.php';

// includes
$wgAutoloadClasses['Wikibase\ByPropertyIdArray'] 		= $dir . 'includes/ByPropertyIdArray.php';
$wgAutoloadClasses['Wikibase\ChangeHandler'] 			= $dir . 'includes/ChangeHandler.php';
$wgAutoloadClasses['Wikibase\ChangeNotifier'] 			= $dir . 'includes/ChangeNotifier.php';
$wgAutoloadClasses['Wikibase\ChangesTable'] 			= $dir . 'includes/ChangesTable.php';
$wgAutoloadClasses['Wikibase\DiffView'] 				= $dir . 'includes/DiffView.php';
$wgAutoloadClasses['Wikibase\Lib\GuidGenerator'] 		= $dir . 'includes/GuidGenerator.php';
$wgAutoloadClasses['Wikibase\Lib\V4GuidGenerator'] 		= $dir . 'includes/GuidGenerator.php';
$wgAutoloadClasses['Wikibase\Lib\ClaimGuidGenerator'] 	= $dir . 'includes/GuidGenerator.php';
$wgAutoloadClasses['Wikibase\HashableObjectStorage']	= $dir . 'includes/HashableObjectStorage.php';
$wgAutoloadClasses['Wikibase\HashArray'] 				= $dir . 'includes/HashArray.php';
$wgAutoloadClasses['Wikibase\LibRegistry'] 				= $dir . 'includes/LibRegistry.php';
$wgAutoloadClasses['Wikibase\Template'] 				= $dir . 'includes/TemplateRegistry.php';
$wgAutoloadClasses['Wikibase\TemplateRegistry'] 		= $dir . 'includes/TemplateRegistry.php';
$wgAutoloadClasses['Wikibase\MapHasher'] 				= $dir . 'includes/MapHasher.php';
$wgAutoloadClasses['Wikibase\MapValueHasher'] 			= $dir . 'includes/MapValueHasher.php';
$wgAutoloadClasses['Wikibase\ReferencedEntitiesFinder'] = $dir . 'includes/ReferencedEntitiesFinder.php';
$wgAutoloadClasses['Wikibase\ObjectComparer'] 			= $dir . 'includes/ObjectComparer.php';
$wgAutoloadClasses['Wikibase\Settings'] 				= $dir . 'includes/Settings.php';
$wgAutoloadClasses['Wikibase\SettingsArray'] 			= $dir . 'includes/SettingsArray.php';
$wgAutoloadClasses['Wikibase\SiteLink'] 				= $dir . 'includes/SiteLink.php';
$wgAutoloadClasses['Wikibase\Term'] 					= $dir . 'includes/Term.php';
$wgAutoloadClasses['Wikibase\Utils'] 					= $dir . 'includes/Utils.php';

// includes/changes
$wgAutoloadClasses['Wikibase\Change'] 				= $dir . 'includes/changes/Change.php';
$wgAutoloadClasses['Wikibase\ChangeRow'] 			= $dir . 'includes/changes/ChangeRow.php';
$wgAutoloadClasses['Wikibase\DiffChange'] 			= $dir . 'includes/changes/DiffChange.php';
$wgAutoloadClasses['Wikibase\EntityChange']			= $dir . 'includes/changes/EntityChange.php';
$wgAutoloadClasses['Wikibase\ItemChange']			= $dir . 'includes/changes/ItemChange.php';

// includes/claims
$wgAutoloadClasses['Wikibase\Claim'] 				= $dir . 'includes/claim/Claim.php';
$wgAutoloadClasses['Wikibase\ClaimAggregate'] 		= $dir . 'includes/claim/ClaimAggregate.php';
$wgAutoloadClasses['Wikibase\ClaimList'] 			= $dir . 'includes/claim/ClaimList.php';
$wgAutoloadClasses['Wikibase\ClaimListAccess'] 		= $dir . 'includes/claim/ClaimListAccess.php';
$wgAutoloadClasses['Wikibase\ClaimObject'] 			= $dir . 'includes/claim/ClaimObject.php';
$wgAutoloadClasses['Wikibase\Claims'] 				= $dir . 'includes/claim/Claims.php';

// includes/entity
$wgAutoloadClasses['Wikibase\Entity'] 				= $dir . 'includes/entity/Entity.php';
$wgAutoloadClasses['Wikibase\EntityDiff'] 			= $dir . 'includes/entity/EntityDiff.php';
$wgAutoloadClasses['Wikibase\EntityDiffer'] 		= $dir . 'includes/entity/EntityDiffer.php';
$wgAutoloadClasses['Wikibase\EntityDiffView'] 		= $dir . 'includes/entity/EntityDiffView.php';
$wgAutoloadClasses['Wikibase\EntityFactory'] 		= $dir . 'includes/entity/EntityFactory.php';
$wgAutoloadClasses['Wikibase\EntityId'] 			= $dir . 'includes/entity/EntityId.php';
$wgAutoloadClasses['Wikibase\Entity'] 				= $dir . 'includes/entity/Entity.php';
$wgAutoloadClasses['Wikibase\EntityPatcher'] 		= $dir . 'includes/entity/EntityPatcher.php';

// includes/item
$wgAutoloadClasses['Wikibase\Item'] 				= $dir . 'includes/item/Item.php';
$wgAutoloadClasses['Wikibase\ItemDiff'] 			= $dir . 'includes/item/ItemDiff.php';
$wgAutoloadClasses['Wikibase\ItemDiffer'] 			= $dir . 'includes/item/ItemDiffer.php';
$wgAutoloadClasses['Wikibase\ItemObject'] 			= $dir . 'includes/item/Item.php';
$wgAutoloadClasses['Wikibase\ItemPatcher'] 			= $dir . 'includes/item/ItemPatcher.php';

// includes/modules
$wgAutoloadClasses['Wikibase\SitesModule'] 				= $dir . 'includes/modules/SitesModule.php';
$wgAutoloadClasses['Wikibase\TemplateModule'] 			= $dir . 'includes/modules/TemplateModule.php';

// includes/property
$wgAutoloadClasses['Wikibase\Property'] 				= $dir . 'includes/property/Property.php';

// includes/query
$wgAutoloadClasses['Wikibase\Query'] 					= $dir . 'includes/query/Query.php';

// includes/reference
$wgAutoloadClasses['Wikibase\Reference'] 				= $dir . 'includes/reference/Reference.php';
$wgAutoloadClasses['Wikibase\ReferenceList'] 			= $dir . 'includes/reference/ReferenceList.php';
$wgAutoloadClasses['Wikibase\ReferenceObject'] 			= $dir . 'includes/reference/ReferenceObject.php';
$wgAutoloadClasses['Wikibase\References'] 				= $dir . 'includes/reference/References.php';

// includes/api/serializers
$wgAutoloadClasses['Wikibase\ByPropertyListSerializer'] = $dir . 'includes/serializers/ByPropertyListSerializer.php';
$wgAutoloadClasses['Wikibase\ClaimSerializer'] 			= $dir . 'includes/serializers/ClaimSerializer.php';
$wgAutoloadClasses['Wikibase\ClaimsSerializer'] 		= $dir . 'includes/serializers/ClaimsSerializer.php';
$wgAutoloadClasses['Wikibase\EntitySerializer'] 		= $dir . 'includes/serializers/EntitySerializer.php';
$wgAutoloadClasses['Wikibase\ItemSerializer'] 			= $dir . 'includes/serializers/ItemSerializer.php';
$wgAutoloadClasses['Wikibase\PropertySerializer'] 		= $dir . 'includes/serializers/PropertySerializer.php';
$wgAutoloadClasses['Wikibase\ReferenceSerializer'] 		= $dir . 'includes/serializers/ReferenceSerializer.php';
$wgAutoloadClasses['Wikibase\SerializationOptions'] 	= $dir . 'includes/serializers/SerializationOptions.php';
$wgAutoloadClasses['Wikibase\EntitySerializationOptions']	= $dir . 'includes/serializers/SerializationOptions.php';
$wgAutoloadClasses['Wikibase\Serializer'] 				= $dir . 'includes/serializers/Serializer.php';
$wgAutoloadClasses['Wikibase\SerializerObject'] 		= $dir . 'includes/serializers/SerializerObject.php';
$wgAutoloadClasses['Wikibase\SnakSerializer'] 			= $dir . 'includes/serializers/SnakSerializer.php';

// includes/snak
$wgAutoloadClasses['Wikibase\PropertyNoValueSnak'] 		= $dir . 'includes/snak/PropertyNoValueSnak.php';
$wgAutoloadClasses['Wikibase\PropertySnak'] 			= $dir . 'includes/snak/PropertySnak.php';
$wgAutoloadClasses['Wikibase\PropertyValueSnak'] 		= $dir . 'includes/snak/PropertyValueSnak.php';
$wgAutoloadClasses['Wikibase\PropertySomeValueSnak'] 	= $dir . 'includes/snak/PropertySomeValueSnak.php';
$wgAutoloadClasses['Wikibase\Snak'] 					= $dir . 'includes/snak/Snak.php';
$wgAutoloadClasses['Wikibase\SnakFactory'] 				= $dir . 'includes/snak/SnakFactory.php';
$wgAutoloadClasses['Wikibase\SnakList'] 				= $dir . 'includes/snak/SnakList.php';
$wgAutoloadClasses['Wikibase\SnakObject'] 				= $dir . 'includes/snak/SnakObject.php';
$wgAutoloadClasses['Wikibase\Snaks'] 					= $dir . 'includes/snak/Snaks.php';

// includes/statement
$wgAutoloadClasses['Wikibase\Statement'] 				= $dir . 'includes/statement/Statement.php';
$wgAutoloadClasses['Wikibase\StatementObject'] 			= $dir . 'includes/statement/StatementObject.php';

// includes/store
$wgAutoloadClasses['Wikibase\EntityLookup'] 			= $dir . 'includes/store/EntityLookup.php';
$wgAutoloadClasses['Wikibase\SiteLinkCache'] 			= $dir . 'includes/store/SiteLinkCache.php';
$wgAutoloadClasses['Wikibase\SiteLinkLookup'] 			= $dir . 'includes/store/SiteLinkLookup.php';
$wgAutoloadClasses['Wikibase\SiteLinkTable'] 			= $dir . 'includes/store/SiteLinkTable.php';
$wgAutoloadClasses['Wikibase\WikiPageEntityLookup'] 	= $dir . 'includes/store/WikiPageEntityLookup.php';

// tests
$wgAutoloadClasses['Wikibase\Test\HashArrayTest'] 			= $dir . 'tests/phpunit/hasharray/HashArrayTest.php';
$wgAutoloadClasses['Wikibase\Test\HashArrayElement'] 		= $dir . 'tests/phpunit/hasharray/HashArrayElement.php';
$wgAutoloadClasses['Wikibase\Test\TemplateTest'] 			= $dir . 'tests/phpunit/TemplateTest.php';
$wgAutoloadClasses['Wikibase\Test\TemplateRegistryTest'] 	= $dir . 'tests/phpunit/TemplateRegistryTest.php';
$wgAutoloadClasses['Wikibase\Test\ChangeRowTest']			= $dir . 'tests/phpunit/changes/ChangeRowTest.php';
$wgAutoloadClasses['Wikibase\Test\EntityChangeTest']		= $dir . 'tests/phpunit/changes/EntityChangeTest.php';
$wgAutoloadClasses['Wikibase\Test\TestChanges']				= $dir . 'tests/phpunit/changes/TestChanges.php';
$wgAutoloadClasses['Wikibase\Test\TestItems'] 				= $dir . 'tests/phpunit/item/TestItems.php';
$wgAutoloadClasses['Wikibase\Test\EntityTest'] 				= $dir . 'tests/phpunit/entity/EntityTest.php';
$wgAutoloadClasses['Wikibase\Test\EntityDiffTest'] 			= $dir . 'tests/phpunit/entity/EntityDiffTest.php';
$wgAutoloadClasses['Wikibase\Test\EntityRefreshTest'] 		= $dir . 'tests/phpunit/changes/EntityRefreshTest.php';
$wgAutoloadClasses['Wikibase\Test\SnakObjectTest'] 			= $dir . 'tests/phpunit/snak/SnakObjectTest.php';
$wgAutoloadClasses['Wikibase\Test\SerializerBaseTest'] 		= $dir . 'tests/phpunit/serializers/SerializerBaseTest.php';
$wgAutoloadClasses['Wikibase\Test\EntitySerializerBaseTest']= $dir . 'tests/phpunit/serializers/EntitySerializerBaseTest.php';
$wgAutoloadClasses['Wikibase\Test\EntityTestCase']          = $dir . 'tests/phpunit/entity/EntityTestCase.php';


$wgDataTypes['wikibase-item'] = array(
	'datavalue' => 'string',
);


// Hooks
$wgHooks['WikibaseDefaultSettings'][]				= 'Wikibase\LibHooks::onWikibaseDefaultSettings';
$wgHooks['UnitTestsList'][]							= 'Wikibase\LibHooks::registerPhpUnitTests';
$wgHooks['ResourceLoaderTestModules'][]				= 'Wikibase\LibHooks::registerQUnitTests';

\Wikibase\TemplateRegistry::singleton()->addTemplates( include( "$dir/resources/templates.php" ) );

/**
 * Shorthand function to retrieve a template filled with the specified parameters.
 *
 * @since 0.2
 *
 * @param $key \string template key
 * Varargs: normal template parameters
 *
 * @return string
 */
function wfTemplate( $key /*...*/ ) {
	$params = func_get_args();
	array_shift( $params );

	if ( isset( $params[0] ) && is_array( $params[0] ) ) {
		$params = $params[0];
	}

	$template = new \Wikibase\Template( \Wikibase\TemplateRegistry::singleton(), $key, $params );
	return $template->text();
}

// Resource Loader Modules:
$wgResourceModules = array_merge( $wgResourceModules, include( "$dir/resources/Resources.php" ) );


$wgWBSettings = array();


unset( $dir );
