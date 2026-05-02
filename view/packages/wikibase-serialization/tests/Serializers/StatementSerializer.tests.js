/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'StatementSerializer' );

	var datamodel = require( 'wikibase.datamodel' ),
		StatementSerializer = require( '../../src/Serializers/StatementSerializer.js' );

	var testSets = [
		[
			new datamodel.Statement(
				new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P1' ) ),
				null,
				datamodel.Statement.RANK.NORMAL
			),
			{
				mainsnak: {
					snaktype: 'novalue',
					property: 'P1'
				},
				type: 'statement',
				rank: 'normal'
			}
		], [
			new datamodel.Statement(
				new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P1' ) ),
				new datamodel.ReferenceList( [ new datamodel.Reference() ] ),
				datamodel.Statement.RANK.PREFERRED
			),
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
			}
		]
	];

	QUnit.test( 'serialize()', function( assert ) {
		assert.expect( 2 );
		var statementSerializer = new StatementSerializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.deepEqual(
				statementSerializer.serialize( testSets[i][0] ),
				testSets[i][1],
				'Test set #' + i + ': Serializing successful.'
			);
		}
	} );

}() );
