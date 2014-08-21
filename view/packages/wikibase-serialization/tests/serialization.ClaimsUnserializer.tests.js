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
			P1234: [
				{
					id: 'Q1234$5678',
					mainsnak: {
						snaktype: 'novalue',
						property: 'P1234'
					},
					type: 'claim',
					rank: 'normal'
				}
			]
		},
		[new wb.datamodel.Claim.newFromJSON( {
			id: 'Q1234$5678',
			mainsnak: {
				snaktype: 'novalue',
				property: 'P1234'
			},
			type: 'claim',
			rank: 'normal'
		} )]
	], [
		{
			P1234: [
				{
					id: 'Q1234$5678',
					mainsnak: {
						snaktype: 'novalue',
						property: 'P1234'
					},
					type: 'claim',
					rank: 'normal'
				}, {
					id: 'Q1234$1234',
					mainsnak: {
						snaktype: 'somevalue',
						property: 'P1234'
					},
					type: 'claim',
					rank: 'normal'
				}
			],
			P5678: [{
				id: 'Q1234$9101112',
				mainsnak: {
					snaktype: 'value',
					property: 'P5678',
					datavalue: {
						value: 'some string',
						type: 'string'
					}
				},
				type: 'statement',
				rank: 'normal'
			}],
			P9874: [{
				id: 'Q5678$5678',
				mainsnak: {
					snaktype: 'somevalue',
					property: 'P9874'
				},
				type: 'claim',
				rank: 'preferred'
			}]
		},
		[
			new wb.datamodel.Claim.newFromJSON( {
				id: 'Q1234$5678',
				mainsnak: {
					snaktype: 'novalue',
					property: 'P1234'
				},
				type: 'claim',
				rank: 'normal'
			} ),
			new wb.datamodel.Claim.newFromJSON( {
				id: 'Q1234$1234',
				mainsnak: {
					snaktype: 'somevalue',
					property: 'P1234'
				},
				type: 'claim',
				rank: 'normal'
			} ),
			new wb.datamodel.Claim.newFromJSON( {
				id: 'Q1234$9101112',
				mainsnak: {
					snaktype: 'value',
					property: 'P5678',
					datavalue: {
						value: 'some string',
						type: 'string'
					}
				},
				type: 'statement',
				rank: 'normal'
			} ),
			new wb.datamodel.Claim.newFromJSON( {
				id: 'Q5678$5678',
				mainsnak: {
					snaktype: 'somevalue',
					property: 'P9874'
				},
				type: 'claim',
				rank: 'preferred'
			} )
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
