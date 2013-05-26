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

if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
	// Include the Ask extension if that hasn't been done yet, since it's required for WikibaseLib to work.
	if ( !defined( 'Ask_VERSION' ) ) {
		@include_once( __DIR__ . '/../../Ask/Ask.php' );
	}
}

$dependencies = array(
	'Diff_VERSION' => 'Diff',
	'DataValues_VERSION' => 'DataValues',
	'ValueParsers_VERSION' => 'ValueParsers',
	'DataTypes_VERSION' => 'DataTypes',
	'ValueView_VERSION' => 'ValueView',
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

$wgExtensionCredits['wikibase'][] = array(
	'path' => __DIR__,
	'name' => 'WikibaseLib',
	'version' => WBL_VERSION,
	'author' => array(
		'The Wikidata team', // TODO: link?
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:WikibaseLib',
	'descriptionmsg' => 'wikibase-lib-desc'
);

foreach ( include( __DIR__ . '/WikibaseLib.classes.php' ) as $class => $file ) {
	$wgAutoloadClasses[$class] = __DIR__ . '/' . $file;
}

define( 'SUMMARY_MAX_LENGTH', 250 );

// i18n
$wgExtensionMessagesFiles['WikibaseLib'] = __DIR__ . '/WikibaseLib.i18n.php';

// TODO: this is not nice, figure out a better design
$wgExtensionFunctions[] = function() {
	global $wgDataTypes;

	$libRegistry = new \Wikibase\LibRegistry( \Wikibase\Settings::singleton() );

	$wgDataTypes['wikibase-item'] = array(
		'datavalue' => 'wikibase-entityid',
		'parser' => $libRegistry->getEntityIdParser(),
		//'formatter' => evilGetEntityidFormatter(), // TODO
	);

	\Wikibase\TemplateRegistry::singleton()->addTemplates( include( __DIR__ . "/resources/templates.php" ) );

    return true;
};

$wgValueParsers['wikibase-entityid'] = 'Wikibase\Lib\EntityIdParser';
$wgDataValues['wikibase-entityid'] = 'Wikibase\EntityId';
$wgJobClasses['ChangeNotification'] = 'Wikibase\ChangeNotificationJob';
$wgJobClasses['UpdateRepoOnMove'] = 'Wikibase\UpdateRepoOnMoveJob';

// Hooks
$wgHooks['UnitTestsList'][]							= 'Wikibase\LibHooks::registerPhpUnitTests';
$wgHooks['ResourceLoaderTestModules'][]				= 'Wikibase\LibHooks::registerQUnitTests';

/**
 * Called when generating the extensions credits, use this to change the tables headers.
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ExtensionTypes
 *
 * @since 0.1
 *
 * @param array &$extensionTypes
 *
 * @return boolean
 */
$wgHooks['ExtensionTypes'][] = function( array &$extensionTypes ) {
	// @codeCoverageIgnoreStart
	$extensionTypes['wikibase'] = wfMessage( 'version-wikibase' )->text();

	return true;
	// @codeCoverageIgnoreEnd
};

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
$wgResourceModules = array_merge( $wgResourceModules, include( __DIR__ . "/resources/Resources.php" ) );

$wgValueFormatters['wikibase-entityid'] = 'Wikibase\Lib\EntityIdFormatter';

if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
	include_once( __DIR__ . '/config/WikibaseLib.experimental.php' );
}

