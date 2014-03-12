/**
 * PhantomJS test runner.
 * PhantomJS will exit with an error code if one ore more tests fail.
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
/* global phantom, require, console */
( function( phantom, require, console ) {
	'use strict';

	var system = require( 'system' );

	if( system.args.length === 1 ) {
		console.error( 'Path to test runner needs to be specified' );
		phantom.exit( 1 );
	}

	var TIMEOUT = 30;

	var page = require( 'webpage' ).create(),
		TestRunner = require( './TestRunner.phantom' ).TestRunner.phantom,
		testRunner = new TestRunner();

	page.onConsoleMessage = function( msg ) {
		var failed = testRunner.onConsoleMessage( msg );
		if( failed !== undefined ) {
			phantom.exit( failed ? 1 : 0 );
		}
	};

	page.open( system.args[1], function( status ) {
		if( status !== 'success' ) {
			console.error( 'Network connection error: ' + status );
			phantom.exit( 1 );
		} else {
			// Set a timeout on the test running, otherwise tests with async problems will hang
			// forever.
			setTimeout( function() {
				console.error( 'The specified timeout of ' + TIMEOUT + ' seconds has expired.' );
				phantom.exit( 1 );
			}, TIMEOUT * 1000 );
		}
	} );

}( phantom, require, console ) );
