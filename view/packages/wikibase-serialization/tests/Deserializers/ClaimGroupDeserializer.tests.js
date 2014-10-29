/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.ClaimGroupDeserializer' );

var testSets = [
	[
		[
			{
				mainsnak: {
					snaktype: 'novalue',
					property: 'P1'
				},
				type: 'claim'
			}
		],
		new wb.datamodel.ClaimGroup( 'P1',
			new wb.datamodel.ClaimList( [
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
			] )
		)
	]
];

QUnit.test( 'deserialize()', function( assert ) {
	var claimGroupDeserializer = new wb.serialization.ClaimGroupDeserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			claimGroupDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Deserializing successful.'
		);
	}

	assert.throws(
		function() {
			claimGroupDeserializer.deserialize( [] );
		},
		'Unable to deserialize an empty array since there is no way to determine the property id '
			+ 'claims shall be grouped with.'
	);
} );

}( wikibase, QUnit ) );
