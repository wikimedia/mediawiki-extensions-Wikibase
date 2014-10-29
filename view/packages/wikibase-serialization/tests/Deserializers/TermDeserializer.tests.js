/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.TermDeserializer' );

var testSets = [
	[
		{ language: 'en', value: 'test' },
		new wb.datamodel.Term( 'en', 'test' )
	]
];

QUnit.test( 'deserialize()', function( assert ) {
	var termDeserializer = new wb.serialization.TermDeserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			termDeserializer.deserialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Deserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
