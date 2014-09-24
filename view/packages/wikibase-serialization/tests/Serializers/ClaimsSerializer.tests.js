/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.ClaimsSerializer' );

var testCases = [
	[
		[new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'Q1$1' )],
		{
			P1: [{
				id: 'Q1$1',
				mainsnak: {
					snaktype: 'novalue',
					property: 'P1'
				},
				type: 'claim'
			} ]
		}
	], [
		[
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'Q1$1' ),
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'Q1$2' ),
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ), null, 'Q2$1' ),
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P3' ), null, 'Q3$1' )
		],
		{
			P1: [
				{
					id: 'Q1$1',
					mainsnak: {
						snaktype: 'novalue',
						property: 'P1'
					},
					type: 'claim'
				}, {
					id: 'Q1$2',
					mainsnak: {
						snaktype: 'novalue',
						property: 'P1'
					},
					type: 'claim'
				}
			],
			P2: [{
				id: 'Q2$1',
				mainsnak: {
					snaktype: 'novalue',
					property: 'P2'
				},
				type: 'claim'
			}],
			P3: [{
				id: 'Q3$1',
				mainsnak: {
					snaktype: 'novalue',
					property: 'P3'
				},
				type: 'claim'
			}]
		}
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var claimsSerializer = new wb.serialization.ClaimsSerializer();

	for( var i = 0; i < testCases.length; i++ ) {
		assert.deepEqual(
			claimsSerializer.serialize( testCases[i][0] ),
			testCases[i][1],
			'Test set #' + i +': Serializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
