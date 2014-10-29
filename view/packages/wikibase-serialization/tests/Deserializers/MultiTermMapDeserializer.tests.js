/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.MultiTermMapDeserializer' );

var testSets = [
	[
		{},
		new wb.datamodel.MultiTermMap()
	], [
		{
			en: [{ language: 'en', value: 'en-test' }],
			de: [{ language: 'de', value: 'de-test' }]
		},
		new wb.datamodel.MultiTermMap( {
			en: new wb.datamodel.MultiTerm( 'en', ['en-test'] ),
			de: new wb.datamodel.MultiTerm( 'de', ['de-test'] )
		} )
	], [
		{
			en: [{ language: 'en', value: 'en-test' }],
			de: [{ language: 'en', value: 'en-test' }]
		},
		new wb.datamodel.MultiTermMap( {
			en: new wb.datamodel.MultiTerm( 'en', ['en-test'] ),
			de: new wb.datamodel.MultiTerm( 'en', ['en-test'] )
		} )
	]
];

QUnit.test( 'deserialize()', function( assert ) {
	var multiTermMapDeserializer = new wb.serialization.MultiTermMapDeserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			multiTermMapDeserializer.deserialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Deserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
