/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit ) {
'use strict';

var MultiTerm = require( '../src/MultiTerm.js' ),
	MultiTermMap = require( '../src/MultiTermMap.js' );

QUnit.module( 'MultiTermMap' );

var testSets = [
	{},
	{
		de: new MultiTerm( 'de', ['de-string'] ),
		en: new MultiTerm( 'en', ['en-string'] )
	},
	{
		de: new MultiTerm( 'en', ['en-string'] ),
		en: new MultiTerm( 'en', ['en-string'] )
	}
];

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 3 );
	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			( new MultiTermMap( testSets[i] ) ) instanceof MultiTermMap,
			'Test set #' + i + ': Instantiated MultiTermMap.'
		);
	}
} );

}( QUnit ) );
