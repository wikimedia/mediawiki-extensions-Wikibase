/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.ClaimsUnserializer' );

var testCases = [
	[
		{
			P1: [{
				id: 'Q1$1',
				mainsnak: {
					snaktype: 'novalue',
					property: 'P1'
				},
				type: 'claim'
			} ]
		},
		[new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'Q1$1' )]
	], [
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
		},
		[
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'Q1$1' ),
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'Q1$2' ),
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ), null, 'Q2$1' ),
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P3' ), null, 'Q3$1' )
		]
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var claimsUnserializer = new wb.serialization.ClaimsUnserializer(),
		error = false;

	assert.deepEqual(
		claimsUnserializer.unserialize(),
		[],
		'Omitting serialization returns an empty array.'
	);

	for( var i = 0; i < testCases.length; i++ ) {
		// Unserializing an object into an array, there is no guarantee that the order matches:
		var claims = claimsUnserializer.unserialize( testCases[i][0] ),
			expectedClaims = testCases[i][1],
			found;

		if( claims.length !== expectedClaims.length ) {
			assert.ok(
				false,
				'Test set #' + i + ': Resulting claims length does match expected claims length.'
			);
			error = true;
		}

		for( var j = 0; j < claims.length; j++ ) {
			found = false;

			for( var k = 0; k < expectedClaims.length; k++ ) {
				if( claims[j].equals( expectedClaims[k] ) ) {
					found = true;
				}
			}

			if( !found ) {
				assert.ok(
					false,
					'Test set #' + i + ': Expected claim #' + k + ' found.'
				);
				error = true;
			}
		}

		if( !error ) {
			assert.ok(
				true,
				'Test set #' + i + ': Unserialization successful.'
			);
		}
	}
} );

}( wikibase, QUnit ) );
