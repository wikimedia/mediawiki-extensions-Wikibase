/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'StatementGroupSetDeserializer' );

	var datamodel = require( 'wikibase.datamodel' ),
		StatementGroupSetDeserializer = require( '../../src/Deserializers/StatementGroupSetDeserializer.js' );

	var testSets = [
		[
			{},
			new datamodel.StatementGroupSet()
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
			] )
		]
	];

	QUnit.test( 'deserialize()', function( assert ) {
		assert.expect( 2 );
		var statementGroupSetDeserializer = new StatementGroupSetDeserializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.ok(
				statementGroupSetDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
				'Test set #' + i + ': Deserializing successful.'
			);
		}
	} );

}() );
