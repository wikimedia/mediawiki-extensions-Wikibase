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

	var URL = './runTests.html',
		TIMEOUT = 30;

	var page = require( 'webpage' ).create(),
		TestRunner = require( '../lib/tests/tests.TestRunner.phantom' ).tests.TestRunner.phantom,
		testRunner = new TestRunner();

	page.onConsoleMessage = function( msg ) {
		var failed = testRunner.onConsoleMessage( msg );
		if( failed !== undefined ) {
			phantom.exit( failed ? 1 : 0 );
		}
	};

	page.open( URL, function( status ) {
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