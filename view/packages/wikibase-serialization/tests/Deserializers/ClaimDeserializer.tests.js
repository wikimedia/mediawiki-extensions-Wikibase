/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	var ClaimDeserializer = require( '../../src/Deserializers/ClaimDeserializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	QUnit.module( 'ClaimDeserializer' );

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
			new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P1' ), null, 'Q1$1' )
		], [
			{
				id: 'Q1$1',
				mainsnak: {
					snaktype: 'novalue',
					property: 'P1'
				},
				qualifiers: {
					P1: [ {
						snaktype: 'novalue',
						property: 'P1'
					} ]
				},
				'qualifiers-order': [ 'P1' ],
				type: 'claim'
			},
			new datamodel.Claim(
				new datamodel.PropertyNoValueSnak( 'P1' ),
				new datamodel.SnakList( [ new datamodel.PropertyNoValueSnak( 'P1' ) ] ),
				'Q1$1'
			)
		]
	];

	QUnit.test( 'deserialize()', function( assert ) {
		assert.expect( 2 );
		var claimDeserializer = new ClaimDeserializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.ok(
				claimDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
				'Test set #' + i + ': Deserializing successful.'
			);
		}
	} );

}() );
