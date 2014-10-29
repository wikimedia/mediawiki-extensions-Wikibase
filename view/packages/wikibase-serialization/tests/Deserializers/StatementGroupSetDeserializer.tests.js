/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.StatementGroupSetDeserializer' );

var testSets = [
	[
		{},
		new wb.datamodel.StatementGroupSet()
	], [
		{
			P1: [
				{
					mainsnak: {
						snaktype: 'novalue',
						property: 'P1'
					},
					type: 'statement',
					rank: 'normal'
				}
			],
			P2: [
				{
					mainsnak: {
						snaktype: 'novalue',
						property: 'P2'
					},
					type: 'statement',
					rank: 'normal'
				}
			]
		},
		new wb.datamodel.StatementGroupSet( [
			new wb.datamodel.StatementGroup( 'P1',
				new wb.datamodel.StatementList( [new wb.datamodel.Statement(
					new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
				)] )
			),
			new wb.datamodel.StatementGroup( 'P2',
				new wb.datamodel.StatementList( [new wb.datamodel.Statement(
					new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
				)] )
			)
		] )
	]
];

QUnit.test( 'deserialize()', function( assert ) {
	var statementGroupSetDeserializer = new wb.serialization.StatementGroupSetDeserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			statementGroupSetDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Deserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
