/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.MultiTermDeserializer' );

var testSets = [
	[
		[{ language: 'en', value: 'test1' }, { language: 'en', value: 'test2' }],
		new wb.datamodel.MultiTerm( 'en', ['test1', 'test2'] )
	]
];

QUnit.test( 'deserialize()', function( assert ) {
	var multiTermDeserializer = new wb.serialization.MultiTermDeserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			multiTermDeserializer.deserialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Deserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
