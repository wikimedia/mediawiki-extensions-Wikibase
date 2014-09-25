/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.TermGroupListSerializer' );

var testSets = [
	[
		new wb.datamodel.TermGroupList( [
			new wb.datamodel.TermGroup( 'en', ['en-test'] ),
			new wb.datamodel.TermGroup( 'de', ['de-test'] )
		] ),
		{
			en: [{ language: 'en', value: 'en-test' }],
			de: [{ language: 'de', value: 'de-test'}]
		}
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var termGroupListSerializer = new wb.serialization.TermGroupListSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			termGroupListSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
