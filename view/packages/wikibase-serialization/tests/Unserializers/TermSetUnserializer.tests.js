/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.TermSetUnserializer' );

var testCases = [
	[
		{
			en: { language: 'en', value: 'en-test' },
			de: { language: 'de', value: 'de-test' }
		},
		new wb.datamodel.TermSet( [
			new wb.datamodel.Term( 'en', 'en-test' ),
			new wb.datamodel.Term( 'de', 'de-test' )
		] )
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var termSetUnserializer = new wb.serialization.TermSetUnserializer();

	for( var i = 0; i < testCases.length; i++ ) {
		assert.deepEqual(
			termSetUnserializer.unserialize( testCases[i][0] ),
			testCases[i][1],
			'Test set #' + i + ': Unserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
