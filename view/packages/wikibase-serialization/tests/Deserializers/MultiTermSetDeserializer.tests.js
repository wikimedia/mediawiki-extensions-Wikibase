/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.MultiTermSetDeserializer' );

var testSets = [
	[
		{
			en: [{ language: 'en', value: 'en-test' }],
			de: [{ language: 'de', value: 'de-test' }]
		},
		new wb.datamodel.MultiTermSet( [
			new wb.datamodel.MultiTerm( 'en', ['en-test'] ),
			new wb.datamodel.MultiTerm( 'de', ['de-test'] )
		] )
	]
];

QUnit.test( 'deserialize()', function( assert ) {
	var multiTermSetDeserializer = new wb.serialization.MultiTermSetDeserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			multiTermSetDeserializer.deserialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Deserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
