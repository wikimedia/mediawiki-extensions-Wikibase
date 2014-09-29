/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.MultiTermSetUnserializer' );

var testCases = [
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

QUnit.test( 'unserialize()', function( assert ) {
	var multiTermSetUnserializer = new wb.serialization.MultiTermSetUnserializer();

	for( var i = 0; i < testCases.length; i++ ) {
		assert.deepEqual(
			multiTermSetUnserializer.unserialize( testCases[i][0] ),
			testCases[i][1],
			'Test set #' + i + ': Unserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
