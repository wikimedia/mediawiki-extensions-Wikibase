<?php

// @codeCoverageIgnoreStart

if ( defined( 'DATA_VALUES_JAVASCRIPT_VERSION' ) ) {
	// Do not initialize more than once.
	return 1;
}

define( 'DATA_VALUES_JAVASCRIPT_VERSION', '0.8.3' );

// Include the composer autoloader if it is present.
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

$GLOBALS['wgExtensionCredits']['datavalues'][] = array(
	'path' => __DIR__,
	'name' => 'DataValues JavaScript',
	'version' => DATA_VALUES_JAVASCRIPT_VERSION,
	'author' => array(
		'[https://www.mediawiki.org/wiki/User:Danwe Daniel Werner]',
		'[http://www.snater.com H. Snater]',
		'[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]',
	),
	'url' => 'https://github.com/wmde/DataValuesJavascript',
	'description' => 'JavaScript related to the DataValues library',
	'license-name' => 'GPL-2.0+'
);

// Resource Loader module registration
$GLOBALS['wgResourceModules'] = array_merge(
	isset( $GLOBALS['wgResourceModules'] ) ? $GLOBALS['wgResourceModules'] : array(),
	include __DIR__ . '/lib/resources.php',
	include __DIR__ . '/src/resources.php'
);

/**
 * Register QUnit test base classes used by test modules in dependent components.
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
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__ . '/tests',
		'remoteExtPath' => '..' . $remoteExtPath[0] . DIRECTORY_SEPARATOR . 'tests',
	);

	$testModuleTemplates = array(

		'valueFormatters.tests' => $moduleTemplate + array(
			'scripts' => array(
				'src/valueFormatters/valueFormatters.tests.js',
			),
			'dependencies' => array(
				'dataValues.DataValue',
				'util.inherit',
				'valueFormatters',
			),
		),

		'valueParsers.tests' => $moduleTemplate + array(
			'scripts' => array(
				'src/valueParsers/valueParsers.tests.js',
			),
			'dependencies' => array(
				'dataValues.DataValue',
				'util.inherit',
				'valueParsers',
			),
		),

	);

	$testModules['qunit'] = array_merge( $testModules['qunit'], $testModuleTemplates );

	return true;
};
