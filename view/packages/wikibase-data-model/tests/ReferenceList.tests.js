/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit ) {
'use strict';

var ReferenceList = require( '../src/ReferenceList.js' ),
	SnakList = require( '../src/SnakList.js' ),
	Reference = require( '../src/Reference.js' );

QUnit.module( 'ReferenceList' );

var testSets = [
	[],
	[
		new Reference( new SnakList(), 'i am a hash' ),
		new Reference( new SnakList(), 'i am another hash' ),
		new Reference()
	]
];

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 2 );
	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			( new ReferenceList( testSets[i] ) ) instanceof ReferenceList,
			'Test set #' + i + ': Instantiated ReferenceList.'
		);
	}
} );

}( QUnit ) );
