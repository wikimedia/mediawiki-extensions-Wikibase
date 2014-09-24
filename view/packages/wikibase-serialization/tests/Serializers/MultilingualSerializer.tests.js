/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.MultilangualSerializer' );

var testSets = [
	[
		{ en: 'test' },
		{ en: { language: 'en', value: 'test' } }
	], [
		{ en: ['test1', 'test2'] },
		{ en: [{ language: 'en', value: 'test1' }, { language: 'en', value: 'test2' }] }
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var multilingualSerializer = new wb.serialization.MultilingualSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			multilingualSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serialization successful.'
		);
	}
} );

}( wikibase, QUnit ) );
