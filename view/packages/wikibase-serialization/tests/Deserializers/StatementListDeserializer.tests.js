/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'StatementListDeserializer' );

	var datamodel = require( 'wikibase.datamodel' ),
		StatementListDeserializer = require( '../../src/Deserializers/StatementListDeserializer.js' );

	var testSets = [
		[
			[],
			new datamodel.StatementList()
		], [
			[
				{
					mainsnak: {
						snaktype: 'novalue',
						property: 'P1'
					},
					type: 'statement',
					rank: 'normal'
				}
			],
			new datamodel.StatementList( [ new datamodel.Statement(
				new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P1' ) )
			) ] )
		]
	];

	QUnit.test( 'deserialize()', function( assert ) {
		assert.expect( 2 );
		var statementListDeserializer = new StatementListDeserializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.ok(
				statementListDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
				'Test set #' + i + ': Deserializing successful.'
			);
		}
	} );

}() );
