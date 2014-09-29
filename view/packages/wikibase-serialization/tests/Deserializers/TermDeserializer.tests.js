/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.TermDeserializer' );

var testCases = [
	[
		{ language: 'en', value: 'test' },
		new wb.datamodel.Term( 'en', 'test' )
	]
];

QUnit.test( 'deserialize()', function( assert ) {
	var termDeserializer = new wb.serialization.TermDeserializer();

	for( var i = 0; i < testCases.length; i++ ) {
		assert.deepEqual(
			termDeserializer.deserialize( testCases[i][0] ),
			testCases[i][1],
			'Test set #' + i + ' deserialized successfully.'
		);
	}
} );

}( wikibase, QUnit ) );
