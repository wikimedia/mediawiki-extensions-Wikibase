/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.MultiTermSetSerializer' );

var testSets = [
	[
		new wb.datamodel.MultiTermSet( [
			new wb.datamodel.MultiTerm( 'en', ['en-test'] ),
			new wb.datamodel.MultiTerm( 'de', ['de-test'] )
		] ),
		{
			en: [{ language: 'en', value: 'en-test' }],
			de: [{ language: 'de', value: 'de-test'}]
		}
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var multiTermSetSerializer = new wb.serialization.MultiTermSetSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			multiTermSetSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
