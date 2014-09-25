/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.TermGroupSerializer' );

var testSets = [
	[
		new wb.datamodel.TermGroup( 'en', ['test1', 'test2'] ),
		[{ language: 'en', value: 'test1' }, { language: 'en', value: 'test2' }]
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var termGroupSerializer = new wb.serialization.TermGroupSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			termGroupSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serialization successful.'
		);
	}
} );

}( wikibase, QUnit ) );
