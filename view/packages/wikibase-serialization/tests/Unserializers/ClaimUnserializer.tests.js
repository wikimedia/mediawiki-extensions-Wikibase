/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.ClaimUnserializer' );

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
			id: 'Q2$2',
			mainsnak: {
				snaktype: 'novalue',
				property: 'P1'
			},
			type: 'statement',
			rank: 'normal'
		},
		new wb.datamodel.Statement(
			new wb.datamodel.PropertyNoValueSnak( 'P1' ),
			null,
			null,
			wb.datamodel.Statement.RANK.NORMAL,
			'Q2$2'
		)
	], [
		{
			id: 'Q3$3',
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
			references: [{
				hash: 'i am a hash',
				snaks: {
					P1: [{
						snaktype: 'novalue',
						property: 'P1'
					}]
				}
			}],
			type: 'statement',
			rank: 'preferred'
		},
		new wb.datamodel.Statement(
			new wb.datamodel.PropertyNoValueSnak( 'P1' ),
			new wb.datamodel.SnakList( [new wb.datamodel.PropertyNoValueSnak( 'P1' )] ),
			[
				new wb.datamodel.Reference(
					[new wb.datamodel.PropertyNoValueSnak( 'P1' )],
					'i am a hash'
				)
			],
			wb.datamodel.Statement.RANK.PREFERRED,
			'Q3$3'
		)
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var claimUnserializer = new wb.serialization.ClaimUnserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			claimUnserializer.unserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Unserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
