/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.StatementGroupSetSerializer' );

var testSets = [
	[
		new wb.datamodel.StatementGroupSet(),
		{}
	], [
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
		] ),
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
		}
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var statementGroupSetSerializer = new wb.serialization.StatementGroupSetSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			statementGroupSetSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
