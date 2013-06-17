<?php

/**
 * TESTING entry point. DO NOT USE FOR REAL SETUPS!
 *
 * This entry point is meant to facilitate development and testing.
 * THIS IS NOT the entry point you want to use in production.
 * For production setups, inclusion of the entry points of
 * the extensions you want to load according to their respective
 * installation instructions is recommended. See the INSTALL
 * and README file for more information.
 *
 * @file
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
define( 'WB_EXPERIMENTAL_FEATURES', true );

require_once __DIR__ . '/lib/WikibaseLib.php';

// Temporary hack that populates the sites table since there are some tests that require this to have happened
require_once __DIR__ . '/lib/maintenance/populateSitesTable.php';
$wgExtensionFunctions[] = function() {
	$evilStuff = new PopulateSitesTable();
	$evilStuff->execute();
};

function remove_directory( $dirPath ) {
	foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
		$path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
	}
}

# Let JenkinsAdapt our test suite when run under Jenkins
$jenkins_job_name = getenv( 'JOB_NAME' );
if( PHP_SAPI === 'cli' && $jenkins_job_name !== false ) {

	switch( $jenkins_job_name) {
		case 'mwext-Wikibase-client-tests':
			remove_directory( __DIR__ . '/Query' );
			remove_directory( __DIR__ . '/repo' );
			require_once __DIR__ . '/client/WikibaseClient.php';
		break;
		case 'mwext-Wikibase-repo-tests':
			remove_directory( __DIR__ . '/Query' );
			remove_directory( __DIR__ . '/client' );
			require_once __DIR__ . '/repo/Wikibase.php';
			require_once __DIR__ . '/repo/ExampleSettings.php';
		break;
		default:
			require_once __DIR__ . '/client/WikibaseClient.php';
			require_once __DIR__ . '/repo/Wikibase.php';
			require_once __DIR__ . '/Query/WikibaseQuery.php';
			require_once __DIR__ . '/repo/ExampleSettings.php';
	}
}
// Avoid polluting the global namespace
unset( $jenkins_job_name, $cmd );
