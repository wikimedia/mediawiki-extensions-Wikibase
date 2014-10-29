/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.TermMapSerializer' );

var testSets = [
	[
		new wb.datamodel.TermMap(),
		{}
	], [
		new wb.datamodel.TermMap( {
			en: new wb.datamodel.Term( 'en', 'en-test' ),
			de: new wb.datamodel.Term( 'de', 'de-test' )
		} ),
		{
			en: { language: 'en', value: 'en-test' },
			de: { language: 'de', value: 'de-test' }
		}
	], [
		new wb.datamodel.TermMap( {
			en: new wb.datamodel.Term( 'en', 'en-test' ),
			de: new wb.datamodel.Term( 'en', 'en-test' )
		} ),
		{
			en: { language: 'en', value: 'en-test' },
			de: { language: 'en', value: 'en-test' }
		}
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var termMapSerializer = new wb.serialization.TermMapSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			termMapSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
