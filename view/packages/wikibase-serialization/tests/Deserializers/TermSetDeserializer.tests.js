/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.TermSetDeserializer' );

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

QUnit.test( 'deserialize()', function( assert ) {
	var termSetDeserializer = new wb.serialization.TermSetDeserializer();

	for( var i = 0; i < testCases.length; i++ ) {
		assert.deepEqual(
			termSetDeserializer.deserialize( testCases[i][0] ),
			testCases[i][1],
			'Test set #' + i + ': Deserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
