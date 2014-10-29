/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.MultiTermMapSerializer' );

var testSets = [
	[
		new wb.datamodel.MultiTermMap(),
		{}
	], [
		new wb.datamodel.MultiTermMap( {
			en: new wb.datamodel.MultiTerm( 'en', ['en-test'] ),
			de: new wb.datamodel.MultiTerm( 'de', ['de-test'] )
		} ),
		{
			en: [{ language: 'en', value: 'en-test' }],
			de: [{ language: 'de', value: 'de-test' }]
		}
	], [
		new wb.datamodel.MultiTermMap( {
			en: new wb.datamodel.MultiTerm( 'en', ['en-test'] ),
			de: new wb.datamodel.MultiTerm( 'en', ['en-test'] )
		} ),
		{
			en: [{ language: 'en', value: 'en-test' }],
			de: [{ language: 'en', value: 'en-test' }]
		}
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var multiTermMapSerializer = new wb.serialization.MultiTermMapSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			multiTermMapSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
