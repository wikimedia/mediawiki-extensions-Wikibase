<?php

/**
 * This is a modified copy of an old version of [core]/tests/phpunit/bootstrap.php which includes only the code required for autoloading.
 */

require_once __DIR__ . '/../../../tests/phpunit/bootstrap.common.php';
$IP = $GLOBALS['IP'];

// Faking in lieu of Setup.php
$GLOBALS['wgAutoloadClasses'] = [];
$GLOBALS['wgBaseDirectory'] = MW_INSTALL_PATH;

TestSetup::requireOnceInGlobalScope( "$IP/includes/AutoLoader.php" );
TestSetup::requireOnceInGlobalScope( "$IP/includes/Defines.php" );
TestSetup::requireOnceInGlobalScope( "$IP/includes/GlobalFunctions.php" );

TestSetup::applyInitialConfig();

// Since we do not load settings, expect to find extensions and skins
// in their respective default locations.
$GLOBALS['wgExtensionDirectory'] = "$IP/extensions";
$GLOBALS['wgStyleDirectory'] = "$IP/skins";

// Populate classes and namespaces from extensions and skins present in filesystem.
$directoryToJsonMap = [
	$GLOBALS['wgExtensionDirectory'] => 'extension*.json',
	$GLOBALS['wgStyleDirectory'] => 'skin*.json',
];

$extensionProcessor = new ExtensionProcessor();

foreach ( $directoryToJsonMap as $directory => $jsonFilePattern ) {
	foreach ( new GlobIterator( $directory . '/*/' . $jsonFilePattern ) as $iterator ) {
		$jsonPath = $iterator->getPathname();
		$extensionProcessor->extractInfoFromFile( $jsonPath );
	}
}

$autoload = $extensionProcessor->getExtractedAutoloadInfo( true );
AutoLoader::loadFiles( $autoload['files'] );
AutoLoader::registerClasses( $autoload['classes'] );
AutoLoader::registerNamespaces( $autoload['namespaces'] );
