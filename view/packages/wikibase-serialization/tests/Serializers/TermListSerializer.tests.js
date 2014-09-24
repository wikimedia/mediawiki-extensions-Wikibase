/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.TermListSerializer' );

var testSets = [
	[
		new wb.datamodel.TermList( [
			new wb.datamodel.Term( 'en', 'en-test' ),
			new wb.datamodel.Term( 'de', 'de-test' )
		] ),
		{
			en: { language: 'en', value: 'en-test' },
			de: { language: 'de', value: 'de-test'}
		}
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var termListSerializer = new wb.serialization.TermListSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			termListSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serialization successful.'
		);
	}
} );

}( wikibase, QUnit ) );
