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

// Include the DataModel component if that hasn't been done yet, since it's required for WikibaseLib to work.
if ( !defined( 'WIKIBASE_DATAMODEL_VERSION' ) ) {
	@include_once( __DIR__ . '/../DataModel/DataModel.php' );
}

// Include the Diff extension if that hasn't been done yet, since it's required for WikibaseLib to work.
if ( !defined( 'Diff_VERSION' ) ) {
	@include_once( __DIR__ . '/../../Diff/Diff.php' );
}

// Include the DataValues extension if that hasn't been done yet, since it's required for WikibaseLib to work.
if ( !defined( 'DataValues_VERSION' ) ) {
	@include_once( __DIR__ . '/../../DataValues/DataValues.php' );
}

// Include the Ask extension if that hasn't been done yet, since it's required for WikibaseLib to work.
if ( !defined( 'Ask_VERSION' ) ) {
	@include_once( __DIR__ . '/../../Ask/Ask.php' );
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

define( 'WBL_VERSION', '0.4 alpha'
	. ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ? '/experimental' : '' ) );

$wgExtensionCredits['other'][] = array(
	'path' => __DIR__,
	'name' => 'WikibaseLib',
	'version' => WBL_VERSION,
	'author' => array(
		'The Wikidata team', // TODO: link?
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:WikibaseLib',
	'descriptionmsg' => 'wikibase-lib-desc'
);

$dir = __DIR__ . '/';

define( 'SUMMARY_MAX_LENGTH', 250 );

// i18n
$wgExtensionMessagesFiles['WikibaseLib'] 			= $dir . 'WikibaseLib.i18n.php';



// Autoloading
$wgAutoloadClasses['Wikibase\LibHooks'] 			= $dir . 'WikibaseLib.hooks.php';

// includes
$wgAutoloadClasses['Wikibase\Arrayalizer'] 				= $dir . 'includes/Arrayalizer.php';
$wgAutoloadClasses['Wikibase\ByPropertyIdArray'] 		= $dir . 'includes/ByPropertyIdArray.php';
$wgAutoloadClasses['Wikibase\CachingEntityLoader']      = $dir . 'includes/store/CachingEntityLoader.php';
$wgAutoloadClasses['Wikibase\ChangeNotifier'] 			= $dir . 'includes/ChangeNotifier.php';
$wgAutoloadClasses['Wikibase\ChangeNotificationJob']	= $dir . 'includes/ChangeNotificationJob.php';
$wgAutoloadClasses['Wikibase\ChangesTable'] 			= $dir . 'includes/ChangesTable.php';
$wgAutoloadClasses['Wikibase\DiffOpValueFormatter']		= $dir . 'includes/DiffOpValueFormatter.php';
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
$wgAutoloadClasses['Wikibase\Term'] 					= $dir . 'includes/Term.php';
$wgAutoloadClasses['Wikibase\Lib\TermsToClaimsTranslator'] = $dir . 'includes/TermsToClaimsTranslator.php';
$wgAutoloadClasses['Wikibase\Utils'] 					= $dir . 'includes/Utils.php';
$wgAutoloadClasses['Wikibase\WikibaseDiffOpFactory']	= $dir . 'includes/WikibaseDiffOpFactory.php';

// includes/changes
$wgAutoloadClasses['Wikibase\Change'] 				= $dir . 'includes/changes/Change.php';
$wgAutoloadClasses['Wikibase\ChangeRow'] 			= $dir . 'includes/changes/ChangeRow.php';
$wgAutoloadClasses['Wikibase\DiffChange'] 			= $dir . 'includes/changes/DiffChange.php';
$wgAutoloadClasses['Wikibase\EntityChange']			= $dir . 'includes/changes/EntityChange.php';
$wgAutoloadClasses['Wikibase\ItemChange']			= $dir . 'includes/changes/ItemChange.php';

$wgAutoloadClasses['Wikibase\ClaimDiffer'] 			= $dir . 'includes/ClaimDiffer.php';
$wgAutoloadClasses['Wikibase\ClaimDifference'] 		= $dir . 'includes/ClaimDifference.php';
$wgAutoloadClasses['Wikibase\ClaimDifferenceVisualizer'] = $dir . 'includes/ClaimDifferenceVisualizer.php';


$wgAutoloadClasses['Wikibase\EntityDiff'] 			= $dir . 'includes/EntityDiff.php';
$wgAutoloadClasses['Wikibase\EntityDiffVisualizer'] = $dir . 'includes/EntityDiffVisualizer.php';
$wgAutoloadClasses['Wikibase\EntityFactory'] 		= $dir . 'includes/EntityFactory.php';
$wgAutoloadClasses['Wikibase\ItemDiff'] 			= $dir . 'includes/ItemDiff.php';

// includes/modules
$wgAutoloadClasses['Wikibase\RepoAccessModule'] 		= $dir . 'includes/modules/RepoAccessModule.php';
$wgAutoloadClasses['Wikibase\SitesModule'] 				= $dir . 'includes/modules/SitesModule.php';
$wgAutoloadClasses['Wikibase\TemplateModule'] 			= $dir . 'includes/modules/TemplateModule.php';

// includes/parsers
$wgAutoloadClasses['Wikibase\Lib\EntityIdParser'] 		= $dir . 'includes/parsers/EntityIdParser.php';


// includes/specials
$wgAutoloadClasses['SpecialWikibasePage'] 				= $dir . 'includes/specials/SpecialWikibasePage.php';
$wgAutoloadClasses['SpecialWikibaseQueryPage']			= $dir . 'includes/specials/SpecialWikibaseQueryPage.php';

// includes/api/serializers
$wgAutoloadClasses['Wikibase\Lib\Serializers\ByPropertyListSerializer'] = $dir . 'includes/serializers/ByPropertyListSerializer.php';
$wgAutoloadClasses['Wikibase\Lib\Serializers\ByPropertyListUnserializer'] = $dir . 'includes/serializers/ByPropertyListUnserializer.php';
$wgAutoloadClasses['Wikibase\Lib\Serializers\ClaimSerializer'] 			= $dir . 'includes/serializers/ClaimSerializer.php';
$wgAutoloadClasses['Wikibase\Lib\Serializers\ClaimsSerializer'] 		= $dir . 'includes/serializers/ClaimsSerializer.php';
$wgAutoloadClasses['Wikibase\Lib\Serializers\EntitySerializer'] 		= $dir . 'includes/serializers/EntitySerializer.php';
$wgAutoloadClasses['Wikibase\Lib\Serializers\ItemSerializer'] 			= $dir . 'includes/serializers/ItemSerializer.php';
$wgAutoloadClasses['Wikibase\Lib\Serializers\PropertySerializer'] 		= $dir . 'includes/serializers/PropertySerializer.php';
$wgAutoloadClasses['Wikibase\Lib\Serializers\ReferenceSerializer'] 		= $dir . 'includes/serializers/ReferenceSerializer.php';
$wgAutoloadClasses['Wikibase\Lib\Serializers\SerializationOptions'] 	= $dir . 'includes/serializers/SerializationOptions.php';
$wgAutoloadClasses['Wikibase\Lib\Serializers\EntitySerializationOptions']	= $dir . 'includes/serializers/SerializationOptions.php';
$wgAutoloadClasses['Wikibase\Lib\Serializers\Serializer'] 				= $dir . 'includes/serializers/Serializer.php';
$wgAutoloadClasses['Wikibase\Lib\Serializers\SerializerFactory'] 		= $dir . 'includes/serializers/SerializerFactory.php';
$wgAutoloadClasses['Wikibase\Lib\Serializers\SerializerObject'] 		= $dir . 'includes/serializers/SerializerObject.php';
$wgAutoloadClasses['Wikibase\Lib\Serializers\SnakSerializer'] 			= $dir . 'includes/serializers/SnakSerializer.php';
$wgAutoloadClasses['Wikibase\Lib\Serializers\Unserializer'] 			= $dir . 'includes/serializers/Unserializer.php';


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

$wgAutoloadClasses['Wikibase\Test\EntityDiffOldTest'] 		= $dir . 'tests/phpunit/entity/EntityDiffOldTest.php';
$wgAutoloadClasses['Wikibase\Test\EntityRefreshTest'] 		= $dir . 'tests/phpunit/changes/EntityRefreshTest.php';
$wgAutoloadClasses['Wikibase\Test\SerializerBaseTest'] 		= $dir . 'tests/phpunit/serializers/SerializerBaseTest.php';
$wgAutoloadClasses['Wikibase\Test\EntitySerializerBaseTest']= $dir . 'tests/phpunit/serializers/EntitySerializerBaseTest.php';
$wgAutoloadClasses['Wikibase\Test\EntityTestCase']			= $dir . 'tests/phpunit/entity/EntityTestCase.php';
$wgAutoloadClasses['Wikibase\Lib\Test\Serializers\UnserializerBaseTest'] = $dir . 'tests/phpunit/serializers/UnserializerBaseTest.php';
$wgAutoloadClasses['Wikibase\Test\MockRepository'] 			= $dir . 'tests/phpunit/MockRepository.php';
$wgAutoloadClasses['Wikibase\Test\EntityLookupTest'] 		= $dir . 'tests/phpunit/EntityLookupTest.php';


// TODO: this is not nice, figure out a better design
$wgExtensionFunctions[] = function() {
	global $wgDataTypes;

	$libRegistry = new \Wikibase\LibRegistry( \Wikibase\Settings::singleton() );

	$wgDataTypes['wikibase-item'] = array(
		'datavalue' => 'wikibase-entityid',
		'parser' => $libRegistry->getEntityIdParser(),
	);

	\Wikibase\TemplateRegistry::singleton()->addTemplates( include( __DIR__ . "/resources/templates.php" ) );

    return true;
};

$wgValueParsers['wikibase-entityid'] = 'Wikibase\Lib\EntityIdParser';
$wgDataValues['wikibase-entityid'] = 'Wikibase\EntityId';
$wgJobClasses['ChangeNotification'] = 'Wikibase\ChangeNotificationJob';

// Hooks
$wgHooks['UnitTestsList'][]							= 'Wikibase\LibHooks::registerPhpUnitTests';
$wgHooks['ResourceLoaderTestModules'][]				= 'Wikibase\LibHooks::registerQUnitTests';

/**
 * Shorthand function to retrieve a template filled with the specified parameters.
 *
 * @since 0.2
 *
 * @param $key string template key
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


include_once( $dir . 'config/WikibaseLib.default.php' );

if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
	include_once( $dir . 'config/WikibaseLib.experimental.php' );
}

unset( $dir );
