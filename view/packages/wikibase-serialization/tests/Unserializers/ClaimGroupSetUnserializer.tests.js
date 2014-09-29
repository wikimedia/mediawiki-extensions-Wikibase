/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.ClaimGroupSetUnserializer' );

var testSets = [
	[
		{},
		new wb.datamodel.ClaimGroupSet()
	], [
		{
			P1: [
				{
					mainsnak: {
						snaktype: 'novalue',
						property: 'P1'
					},
					type: 'claim',
					rank: 'normal'
				}
			],
			P2: [
				{
					mainsnak: {
						snaktype: 'novalue',
						property: 'P2'
					},
					type: 'claim',
					rank: 'normal'
				}
			]
		},
		new wb.datamodel.ClaimGroupSet( [
			new wb.datamodel.ClaimGroup( 'P1',
				new wb.datamodel.ClaimList( [
					new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
				] )
			),
			new wb.datamodel.ClaimGroup( 'P2',
				new wb.datamodel.ClaimList( [
					new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
				] )
			)
		] )
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var claimGroupSetUnserializer = new wb.serialization.ClaimGroupSetUnserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			claimGroupSetUnserializer.unserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Unserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
