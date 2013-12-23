<?php

/**
 * MediaWiki setup for the "ValueView" extension.
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

global $wgExtensionCredits, $wgExtensionMessagesFiles, $wgHooks, $wgResourceModules;

$wgExtensionCredits['datavalues'][] = array(
	'path' => __DIR__,
	'name' => 'ValueView',
	'version' => VALUEVIEW_VERSION,
	'author' => array(
		'[https://www.mediawiki.org/wiki/User:Danwe Daniel Werner]'
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:ValueView',
	'descriptionmsg' => 'valueview-desc',
);

$wgExtensionMessagesFiles['ValueView'] = __DIR__ . '/ValueView.i18n.php';

/**
 * Hook for registering QUnit test cases.
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
 * @since 0.1
 *
 * @param array &$testModules
 * @param \ResourceLoader &$resourceLoader
 * @return boolean
 */
$wgHooks['ResourceLoaderTestModules'][] = function ( array &$testModules, \ResourceLoader &$resourceLoader ) {
	// Register jQuery.valueview QUnit tests. Take the predefined test definitions and make them
	// suitable for registration with MediaWiki's resource loader.
	$ownModules = include( __DIR__ . '/ValueView.tests.qunit.php' );
	$ownModulesTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' =>  '..' . substr( __DIR__, strlen( $GLOBALS['IP'] ) ),
	);
	foreach( $ownModules as $ownModuleName => $ownModule ) {
		$testModules['qunit'][ $ownModuleName ] = $ownModule + $ownModulesTemplate;
	}
	return true;
};

// Resource Loader module registration
$wgResourceModules = array_merge(
	$wgResourceModules,
	include( __DIR__ . '/ValueView.resources.mw.php' )
);
