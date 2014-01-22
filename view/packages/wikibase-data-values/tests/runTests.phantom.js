/**
 * PhantomJS testrunner.
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

	var page = require( 'webpage' ).create();

	page.onConsoleMessage = function( msg ) {
		console.log( msg );

		if( msg.indexOf( 'TEST END' ) === 0 ) {
			var msgParts = msg.match( /(\d{1,}) failure/ ),
				failures = parseInt( msgParts[1], 10 );

			if( failures > 0 ) {
				phantom.exit( 1 );
			}

			phantom.exit( 0 );
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