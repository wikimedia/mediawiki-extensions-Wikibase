/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit ) {
'use strict';

var Term = require( '../src/Term.js' ),
	TermMap = require( '../src/TermMap.js' );

QUnit.module( 'TermMap' );

var testSets = [
	{},
	{
		de: new Term( 'de', 'de-string' ),
		en: new Term( 'en', 'en-string' )
	},
	{
		de: new Term( 'en', 'en-string' ),
		en: new Term( 'en', 'en-string' )
	}
];

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 3 );
	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			( new TermMap( testSets[i] ) ) instanceof TermMap,
			'Test set #' + i + ': Instantiated TermMap.'
		);
	}
} );

}( QUnit ) );
