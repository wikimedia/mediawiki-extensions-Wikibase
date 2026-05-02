/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'ClaimSerializer' );

	var datamodel = require( 'wikibase.datamodel' ),
		ClaimSerializer = require( '../../src/Serializers/ClaimSerializer.js' );

	var testSets = [
		[
			new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P1' ), null, 'Q1$1' ),
			{
				id: 'Q1$1',
				mainsnak: {
					snaktype: 'novalue',
					property: 'P1'
				},
				type: 'claim'
			}
		], [
			new datamodel.Claim(
				new datamodel.PropertyNoValueSnak( 'P1' ),
				new datamodel.SnakList( [ new datamodel.PropertyNoValueSnak( 'P1' ) ] ),
				'Q1$1'
			),
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
			}
		]
	];

	QUnit.test( 'serialize()', function( assert ) {
		assert.expect( 2 );
		var claimSerializer = new ClaimSerializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.deepEqual(
				claimSerializer.serialize( testSets[i][0] ),
				testSets[i][1],
				'Test set #' + i + ': Serializing successful.'
			);
		}
	} );

}() );
