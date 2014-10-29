/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.ClaimGroupSetSerializer' );

var testSets = [
	[
		new wb.datamodel.ClaimGroupSet(),
		{}
	], [
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
		] ),
		{
			P1: [
				{
					mainsnak: {
						snaktype: 'novalue',
						property: 'P1'
					},
					type: 'claim'
				}
			],
			P2: [
				{
					mainsnak: {
						snaktype: 'novalue',
						property: 'P2'
					},
					type: 'claim'
				}
			]
		}
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var claimGroupSetSerializer = new wb.serialization.ClaimGroupSetSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			claimGroupSetSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
