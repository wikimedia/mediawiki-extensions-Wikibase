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
 * @author Daniel Kinzler
 */

//TODO: Use a different file for jenkins, use this for a standard repo+client setup.

$jenkins_job_name = getenv( 'JOB_NAME' );

if( PHP_SAPI !== 'cli' || $jenkins_job_name === false ) {
	die( "This entry point is for use by the Jenkins testing framework only.\n"
		. "Use repo/Wikibase.php resp. client/WikibaseClient.php instead.\n" );
}

if ( !defined( 'WB_EXPERIMENTAL_FEATURES' ) ) {
	define( 'WB_EXPERIMENTAL_FEATURES', true );
}

switch( $jenkins_job_name) {
	case 'mwext-Wikibase-client-tests':
		require_once __DIR__ . '/client/WikibaseClient.php';
		require_once __DIR__ . '/client/ExampleSettings.php';
		break;

	case 'mwext-Wikibase-repo-tests':
		require_once __DIR__ . '/repo/Wikibase.php';
		require_once __DIR__ . '/repo/ExampleSettings.php';
		break;

	default:
		require_once __DIR__ . '/repo/Wikibase.php';
		require_once __DIR__ . '/client/WikibaseClient.php';

		require_once __DIR__ . '/repo/ExampleSettings.php';
		require_once __DIR__ . '/client/ExampleSettings.php';
}

// Avoid polluting the global namespace
unset( $jenkins_job_name );


