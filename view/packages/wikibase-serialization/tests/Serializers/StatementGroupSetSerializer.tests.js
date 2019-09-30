/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.StatementGroupSetSerializer' );

var datamodel = require( 'wikibase.datamodel' );

var testSets = [
	[
		new datamodel.StatementGroupSet(),
		{}
	], [
		new datamodel.StatementGroupSet( [
			new datamodel.StatementGroup( 'P1',
				new datamodel.StatementList( [ new datamodel.Statement(
					new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P1' ) )
				) ] )
			),
			new datamodel.StatementGroup( 'P2',
				new datamodel.StatementList( [ new datamodel.Statement(
					new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P2' ) )
				) ] )
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
	assert.expect( 2 );
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
