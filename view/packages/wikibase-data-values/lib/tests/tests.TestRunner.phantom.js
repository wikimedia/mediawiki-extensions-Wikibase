/**
 * PhantomJS test runner module corresponding to the native test runner implementation.
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

/* global tests, exports, console */
this.tests = this.tests || {};

this.tests.TestRunner = this.tests.TestRunner || {};

this.tests.TestRunner.phantom = ( function( console ) {
	'use strict';

	var TestRunner = function() {};

	/**
	 * Evaluates the console messages generated in the native TestRunner.
	 *
	 * @param {string} msg
	 * @return {boolean|undefined}
	 */
	TestRunner.prototype.onConsoleMessage = function( msg ) {
		console.log( msg );

		if( msg.indexOf( 'TEST END' ) === 0 ) {
			var msgParts = msg.match( /(\d{1,}) failure/ );
			return ( parseInt( msgParts[1], 10 ) > 0 );
		}

		return undefined;
	};

	return TestRunner;

}( console ) );

exports.tests = this.tests;