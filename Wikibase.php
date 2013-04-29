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

require_once __DIR__ . '/Query/ExampleSettings.php';

require_once __DIR__ . '/DataModel/DataModel.php';
require_once __DIR__ . '/lib/WikibaseLib.php';
require_once __DIR__ . '/repo/Wikibase.php';
require_once __DIR__ . '/Query/WikibaseQuery.php';
//require_once __DIR__ . '/client/WikibaseClient.php';

//require_once __DIR__ . '/client/ExampleSettings.php';
require_once __DIR__ . '/repo/ExampleSettings.php';

# Let JenkinsAdapt our test suite when run under Jenkins
$jenkins_job_name = getenv( 'JOB_NAME' );
if( PHP_SAPI === 'cli' && $jenkins_job_name !== false ) {

	switch( $jenkins_job_name) {

	case 'mwext-Wikibase-client-tests':
	break;

	case 'mwext-Wikibase-server-tests':
		# Pretends we asked PHPUnit to exclude WikidataClient group,
		# this is done by inserting an --exclude-group option just after the
		# command line.
		$cmd = array_shift( $_SERVER['argv'] );
		$_SERVER['argv'] = array_merge(
			array( $cmd, '--exclude-group', 'Wikibase'),
			$_SERVER['argv']
		);
	break;
	}
}
// Avoid polluting the global namespace
unset( $jenkins_job_name, $cmd );
