/**
 * QUnit test initialization.
 * Loads the test configuration via require.js and triggers running the tests. If no single test
 * module has been specified using the "testModule" URL parameter, all tests will be executed at
 * once.
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
/* global testConfig */
( function( require, testConfig ) {
	'use strict';

	var queryString = ( function( urlParams ) {
		var params = {};
		if( urlParams.length === 1 && urlParams[0] === '' ) {
			return params;
		}
		for( var i = 0; i < urlParams.length; i++ ) {
			var param = urlParams[i].split( '=' );
			if( param.length === 2 ) {
				params[param[0]] = decodeURIComponent( param[1].replace( /\+/g, ' ' ) );
			}
		}
		return params;
	} )( window.location.search.substr( 1 ).split( '&' ) );

	var testModules = [],
		testModule = queryString.testModule;

	if( testModule !== undefined ) {
		testModules = [testModule];
	} else {
		// Run all tests at once.
		for( var module in testConfig.paths ) {
			if( /\.tests$/.test( module ) ) {
				testModules.push( module );
			}
		}
	}

	require.config( testConfig );

	require( testModules, function() {
		QUnit.load();
		QUnit.start();
	} );

} )( require, testConfig );
