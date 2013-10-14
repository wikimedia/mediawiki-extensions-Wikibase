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
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Adam Shorland
 */

//TODO: Use a different file for jenkins, use this for a standard repo+client setup.

if( isset( $wgWikimediaJenkinsCI ) && !$wgWikimediaJenkinsCI ) {
	die( "This entry point is for use by the Jenkins testing framework only.\n"
		. "Use repo/Wikibase.php resp. client/WikibaseClient.php instead.\n" );
}

if ( !defined( 'WB_EXPERIMENTAL_FEATURES' ) ) {
	define( 'WB_EXPERIMENTAL_FEATURES', true );
}

switch( getenv( 'JOB_NAME' ) ) {
	case 'mwext-Wikibase-client-tests':
		require_once __DIR__ . '/client/WikibaseClient.php';
		require_once __DIR__ . '/client/ExampleSettings.php';
		break;

	case 'mwext-Wikibase-repo-tests':
		require_once __DIR__ . '/repo/Wikibase.php';
		require_once __DIR__ . '/repo/ExampleSettings.php';
		$_SERVER['argv'] = array_merge( $_SERVER['argv'], array(
			'--exclude-group', 'WikibaseAPI',
		) );
		break;

	case 'mwext-Wikibase-repoapi-tests':
		require_once __DIR__ . '/repo/Wikibase.php';
		require_once __DIR__ . '/repo/ExampleSettings.php';
		$_SERVER['argv'] = array_merge( $_SERVER['argv'], array(
			'--group', 'WikibaseAPI',
		) );
		break;

	// mwext-Wikibase-testextensions-master ( or other jobs )
	default:
		require_once __DIR__ . '/repo/Wikibase.php';
		require_once __DIR__ . '/client/WikibaseClient.php';

		require_once __DIR__ . '/repo/ExampleSettings.php';
		require_once __DIR__ . '/client/ExampleSettings.php';
		break;
}

// Avoid polluting the global namespace
unset( $jenkins_job_name );