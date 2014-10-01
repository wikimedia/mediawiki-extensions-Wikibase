/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.EntityIdDeserializer' );

var testSets = [
	[
		[
			'entity type',
			'P1'
		],
		new wb.datamodel.EntityId( 'entity type', 'P1' )
	]
];

QUnit.test( 'deserialize()', function( assert ) {
	var entityIdDeserializer = new wb.serialization.EntityIdDeserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			entityIdDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Deserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
