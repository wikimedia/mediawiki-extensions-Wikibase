/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.ClaimGroupUnserializer' );

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

QUnit.test( 'unserialize()', function( assert ) {
	var claimGroupUnserializer = new wb.serialization.ClaimGroupUnserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			claimGroupUnserializer.unserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Unserializing successful.'
		);
	}

	assert.throws(
		function() {
			claimGroupUnserializer.unserialize( [] );
		},
		'Unable to unserialize an empty array since there is no way to determine the property id '
			+ 'claims shall be grouped with.'
	);
} );

}( wikibase, QUnit ) );
