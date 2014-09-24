/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.TermSerializer' );

var testSets = [
	[
		new wb.datamodel.Term( 'en', 'test' ),
		{ language: 'en', value: 'test' }
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var termSerializer = new wb.serialization.TermSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			termSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serialization successful.'
		);
	}
} );

}( wikibase, QUnit ) );
