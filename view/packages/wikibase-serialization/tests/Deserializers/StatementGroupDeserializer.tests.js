/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	var StatementGroupDeserializer = require( '../../src/Deserializers/StatementGroupDeserializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	QUnit.module( 'StatementGroupDeserializer' );

	var testSets = [
		[
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
			new datamodel.StatementGroup( 'P1',
				new datamodel.StatementList( [ new datamodel.Statement(
					new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P1' ) )
				) ] )
			)
		]
	];

	QUnit.test( 'deserialize()', function( assert ) {
		assert.expect( 2 );
		var statementGroupDeserializer = new StatementGroupDeserializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.ok(
				statementGroupDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
				'Test set #' + i + ': Deserializing successful.'
			);
		}

		assert.throws(
			function() {
				statementGroupDeserializer.deserialize( [] );
			},
			'Unable to deserialize an empty array since there is no way to determine the property id '
				+ 'statements shall be grouped with.'
		);
	} );

}() );
