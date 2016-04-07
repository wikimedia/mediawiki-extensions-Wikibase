/**
 * PhantomJS test runner module corresponding to the native test runner implementation.
 *
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */

/* global exports, console */
this.TestRunner = this.TestRunner || {};

this.TestRunner.phantom = ( function( console ) {
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

exports.TestRunner = this.TestRunner;
