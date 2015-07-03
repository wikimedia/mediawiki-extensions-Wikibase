<?php
/**
 * MediaWiki setup for the "ValueView" extension.
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

global $wgExtensionCredits, $wgExtensionMessagesFiles, $wgHooks, $wgResourceModules, $wgMessagesDirs;

$wgExtensionCredits['other'][] = array(
	'path' => __DIR__,
	'name' => 'ValueView',
	'version' => VALUEVIEW_VERSION,
	'author' => array(
		'[https://www.mediawiki.org/wiki/User:Danwe Daniel Werner]',
		'[http://www.snater.com H. Snater]',
	),
	'url' => 'https://github.com/wmde/ValueView',
	'descriptionmsg' => 'valueview-desc',
	'license-name' => 'GPL-2.0+'
);

$wgMessagesDirs['ValueView'] = __DIR__ . '/i18n';

/**
 * Register QUnit test cases.
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
 * @since 0.1
 *
 * @param array &$testModules
 * @param \ResourceLoader &$resourceLoader
 * @return boolean
 */
$wgHooks['ResourceLoaderTestModules'][] = function(
	array &$testModules,
	\ResourceLoader &$resourceLoader
) {
	$testModules['qunit'] = array_merge(
		$testModules['qunit'],
		include __DIR__ . '/tests/lib/resources.php',
		include __DIR__ . '/tests/src/resources.php'
	);
	return true;
};

// Register Resource Loader modules:
$wgResourceModules = array_merge(
	$wgResourceModules,
	include __DIR__ . '/lib/resources.php',
	include __DIR__ . '/src/resources.php'
);
