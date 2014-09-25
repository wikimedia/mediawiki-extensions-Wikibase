/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.TermGroupListUnserializer' );

var testCases = [
	[
		{
			en: [{ language: 'en', value: 'en-test' }],
			de: [{ language: 'de', value: 'de-test' }]
		},
		new wb.datamodel.TermGroupList( [
			new wb.datamodel.TermGroup( 'en', ['en-test'] ),
			new wb.datamodel.TermGroup( 'de', ['de-test'] )
		] )
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var termGroupListUnserializer = new wb.serialization.TermGroupListUnserializer();

	for( var i = 0; i < testCases.length; i++ ) {
		assert.deepEqual(
			termGroupListUnserializer.unserialize( testCases[i][0] ),
			testCases[i][1],
			'Test set #' + i + ': Unserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
