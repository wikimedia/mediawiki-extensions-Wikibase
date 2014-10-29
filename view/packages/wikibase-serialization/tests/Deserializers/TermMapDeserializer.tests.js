/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.TermMapDeserializer' );

var testSets = [
	[
		{},
		new wb.datamodel.TermMap()
	], [
		{
			en: { language: 'en', value: 'en-test' },
			de: { language: 'de', value: 'de-test' }
		},
		new wb.datamodel.TermMap( {
			en: new wb.datamodel.Term( 'en', 'en-test' ),
			de: new wb.datamodel.Term( 'de', 'de-test' )
		} )
	], [
		{
			en: { language: 'en', value: 'en-test' },
			de: { language: 'en', value: 'en-test' }
		},
		new wb.datamodel.TermMap( {
			en: new wb.datamodel.Term( 'en', 'en-test' ),
			de: new wb.datamodel.Term( 'en', 'en-test' )
		} )
	]
];

QUnit.test( 'deserialize()', function( assert ) {
	var termMapDeserializer = new wb.serialization.TermMapDeserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			termMapDeserializer.deserialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Deserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
