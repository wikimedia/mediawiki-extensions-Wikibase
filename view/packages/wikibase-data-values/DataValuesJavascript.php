<?php

// @codeCoverageIgnoreStart

if ( defined( 'DATA_VALUES_JAVASCRIPT_VERSION' ) ) {
	// Do not initialize more than once.
	return 1;
}

define( 'DATA_VALUES_JAVASCRIPT_VERSION', '0.1.1.1' );

// Include the composer autoloader if it is present.
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	include_once( __DIR__ . '/vendor/autoload.php' );
}

$GLOBALS['wgExtensionCredits']['datavalues'][] = array(
	'path' => __DIR__,
	'name' => 'DataValues Javascript',
	'version' => DATA_VALUES_JAVASCRIPT_VERSION,
	'author' => array(
		'[https://www.mediawiki.org/wiki/User:Danwe Daniel Werner]',
		'[http://www.snater.com H. Snater]',
		'[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]',
	),
);

// Resource Loader module registration
$GLOBALS['wgResourceModules'] = array_merge(
	$GLOBALS['wgResourceModules'],
	include( __DIR__ . '/lib/resources.php' ),
	include( __DIR__ . '/src/resources.mw.php' ),
	include( __DIR__ . '/src/valueParsers/resources.mw.php' ),
	include( __DIR__ . '/src/valueFormatters/resources.mw.php' )
);

// API module registration
$GLOBALS['wgAPIModules']['parsevalue'] = 'ValueParsers\ApiParseValue';

/**
 * @deprecated since 0.1 This is a global registry that provides no control over object lifecycle
 */
$GLOBALS['wgValueParsers'] = array();

/**
 * Hook for registering QUnit test cases.
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
 * @since 0.1
 *
 * @param array &$testModules
 * @param \ResourceLoader &$resourceLoader
 * @return boolean
 */
$GLOBALS['wgHooks']['ResourceLoaderTestModules'][] = function(
	array &$testModules,
	\ResourceLoader &$resourceLoader
) {
	$testModules['qunit'] = array_merge(
		$testModules['qunit'],
		include( __DIR__ . '/tests/lib/resources.php' ),
		include( __DIR__ . '/tests/src/resources.php' ),
		include( __DIR__ . '/tests/src/valueFormatters/resources.php' ),
		include( __DIR__ . '/tests/src/valueParsers/resources.php' )
	);

	return true;
};
