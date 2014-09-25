/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.ClaimListUnserializer' );

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

QUnit.test( 'unserialize()', function( assert ) {
	var claimListUnserializer = new wb.serialization.ClaimListUnserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			claimListUnserializer.unserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Unserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
