/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.ReferenceListDeserializer' );

var testSets = [
	[
		[],
		new wb.datamodel.ReferenceList()
	], [
		[
			{
				snaks: {},
				'snaks-order': []
			}
		],
		new wb.datamodel.ReferenceList( [new wb.datamodel.Reference()] )
	], [
		[
			{
				snaks: {},
				'snaks-order': [],
				hash: 'hash1'
			}, {
				snaks: {},
				'snaks-order': [],
				hash: 'hash2'
			}
		],
		new wb.datamodel.ReferenceList( [
			new wb.datamodel.Reference( null, 'hash1' ),
			new wb.datamodel.Reference( null, 'hash2' )
		] )
	]
];

QUnit.test( 'deserialize()', function( assert ) {
	var referenceListDeserializer = new wb.serialization.ReferenceListDeserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			referenceListDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Deserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
