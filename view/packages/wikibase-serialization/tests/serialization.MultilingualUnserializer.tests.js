/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.MultilangualUnserializer' );

var testCases = [
	[
		{ en: { language: 'en', value: 'test' } },
		{ en: 'test' }
	], [
		{ en: [{ language: 'en', value: 'test1' }, { language: 'en', value: 'test2' }] },
		{ en: ['test1', 'test2'] }
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var multilingualUnserializer = new wb.serialization.MultilingualUnserializer();

	assert.deepEqual(
		multilingualUnserializer.unserialize(),
		{},
		'Omitting serialization returns an empty object.'
	);

	for( var i = 0; i < testCases.length; i++ ) {
		assert.deepEqual(
			multilingualUnserializer.unserialize( testCases[i][0] ),
			testCases[i][1],
			'Successful unserialization of test set #' + i
		);
	}
} );

}( jQuery, wikibase, QUnit ) );
