/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.EntityIdUnserializer' );

var testSets = [
	[
		{
			'entity-type': 'entity type',
			'numeric-id': 1
		},
		new wb.datamodel.EntityId( 'entity type', 1 )
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var entityIdUnserializer = new wb.serialization.EntityIdUnserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			entityIdUnserializer.unserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Unserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
