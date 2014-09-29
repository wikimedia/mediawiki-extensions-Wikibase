/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.MultiTermUnserializer' );

var testSets = [
	[
		[{ language: 'en', value: 'test1' }, { language: 'en', value: 'test2' }],
		new wb.datamodel.MultiTerm( 'en', ['test1', 'test2'] )
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var multiTermUnserializer = new wb.serialization.MultiTermUnserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			multiTermUnserializer.unserialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Unserialization successful.'
		);
	}
} );

}( wikibase, QUnit ) );
