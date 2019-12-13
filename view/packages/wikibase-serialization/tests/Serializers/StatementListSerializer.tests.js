/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'StatementListSerializer' );

	var datamodel = require( 'wikibase.datamodel' ),
		StatementListSerializer = require( '../../src/Serializers/StatementListSerializer.js' );

	var testSets = [
		[
			new datamodel.StatementList(),
			[]
		], [
			new datamodel.StatementList( [ new datamodel.Statement(
				new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P1' ) )
			) ] ),
			[
				{
					mainsnak: {
						snaktype: 'novalue',
						property: 'P1'
					},
					type: 'statement',
					rank: 'normal'
				}
			]
		]
	];

	QUnit.test( 'serialize()', function( assert ) {
		assert.expect( 2 );
		var statementListSerializer = new StatementListSerializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.deepEqual(
				statementListSerializer.serialize( testSets[i][0] ),
				testSets[i][1],
				'Test set #' + i + ': Serializing successful.'
			);
		}
	} );

}() );
