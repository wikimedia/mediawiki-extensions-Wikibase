/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.ClaimListSerializer' );

var testSets = [
	[
		new wb.datamodel.ClaimList(),
		[]
	], [
		new wb.datamodel.ClaimList( [
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		] ),
		[
			{
				mainsnak: {
					snaktype: 'novalue',
					property: 'P1'
				},
				type: 'claim'
			}
		]
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var claimListSerializer = new wb.serialization.ClaimListSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			claimListSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
