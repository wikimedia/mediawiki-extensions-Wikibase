/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.ClaimDeserializer' );

var testSets = [
	[
		{
			id: 'Q1$1',
			mainsnak: {
				snaktype: 'novalue',
				property: 'P1'
			},
			type: 'claim'
		},
		new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'Q1$1' )
	], [
		{
			id: 'Q1$1',
			mainsnak: {
				snaktype: 'novalue',
				property: 'P1'
			},
			qualifiers: {
				P1: [{
					snaktype: 'novalue',
					property: 'P1'
				}]
			},
			'qualifiers-order': ['P1'],
			type: 'claim'
		},
		new wb.datamodel.Claim(
			new wb.datamodel.PropertyNoValueSnak( 'P1' ),
			new wb.datamodel.SnakList( [new wb.datamodel.PropertyNoValueSnak( 'P1' )] ),
			'Q1$1'
		)
	]
];

QUnit.test( 'deserialize()', function( assert ) {
	var claimDeserializer = new wb.serialization.ClaimDeserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			claimDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Deserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
