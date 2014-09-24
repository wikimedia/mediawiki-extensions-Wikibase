/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.TermUnserializer' );

var testCases = [
	[
		{ language: 'en', value: 'test' },
		new wb.datamodel.Term( 'en', 'test' )
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var termUnserializer = new wb.serialization.TermUnserializer();

	for( var i = 0; i < testCases.length; i++ ) {
		assert.deepEqual(
			termUnserializer.unserialize( testCases[i][0] ),
			testCases[i][1],
			'Test set #' + i + ' unserialized successfully.'
		);
	}
} );

}( wikibase, QUnit ) );
