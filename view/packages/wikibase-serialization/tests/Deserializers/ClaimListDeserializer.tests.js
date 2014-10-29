/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.ClaimListDeserializer' );

var testSets = [
	[
		[],
		new wb.datamodel.ClaimList()
	], [
		[
			{
				mainsnak: {
					snaktype: 'novalue',
					property: 'P1'
				},
				type: 'claim'
			}
		],
		new wb.datamodel.ClaimList( [
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		] )
	]
];

QUnit.test( 'deserialize()', function( assert ) {
	var claimListDeserializer = new wb.serialization.ClaimListDeserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			claimListDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Deserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
