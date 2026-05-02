/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'StatementDeserializer' );

	var datamodel = require( 'wikibase.datamodel' ),
		StatementDeserializer = require( '../../src/Deserializers/StatementDeserializer.js' );

	var testSets = [
		[
			{
				mainsnak: {
					snaktype: 'novalue',
					property: 'P1'
				},
				type: 'statement',
				rank: 'normal'
			},
			new datamodel.Statement(
				new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P1' ) ),
				null,
				datamodel.Statement.RANK.NORMAL
			)
		], [
			{
				mainsnak: {
					snaktype: 'novalue',
					property: 'P1'
				},
				references: [ {
					snaks: {},
					'snaks-order': []
				} ],
				type: 'statement',
				rank: 'preferred'
			},
			new datamodel.Statement(
				new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P1' ) ),
				new datamodel.ReferenceList( [ new datamodel.Reference() ] ),
				datamodel.Statement.RANK.PREFERRED
			)
		]
	];

	QUnit.test( 'deserialize()', function( assert ) {
		assert.expect( 2 );
		var statementDeserializer = new StatementDeserializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.ok(
				statementDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
				'Test set #' + i + ': Deserializing successful.'
			);
		}
	} );

}() );
