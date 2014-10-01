/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.EntityIdSerializer' );

var testSets = [
	[
		new wb.datamodel.EntityId( 'entity type', 'P1' ),
		[
			'entity type',
			'P1'
		]
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var entityIdSerializer = new wb.serialization.EntityIdSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			entityIdSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
