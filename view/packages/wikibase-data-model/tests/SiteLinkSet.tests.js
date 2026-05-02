/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit ) {
'use strict';

var SiteLink = require( '../src/SiteLink.js' ),
	SiteLinkSet = require( '../src/SiteLinkSet.js' );

QUnit.module( 'SiteLinkSet' );

var testSets = [
	[],
	[
		new SiteLink( 'de', 'de-page' ),
		new SiteLink( 'en', 'en-page' )
	]
];

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 2 );
	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			( new SiteLinkSet( testSets[i] ) ) instanceof SiteLinkSet,
			'Test set #' + i + ': Instantiated SiteLinkSet.'
		);
	}
} );

}( QUnit ) );
