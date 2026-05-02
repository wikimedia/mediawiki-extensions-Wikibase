/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( dv ) {
	'use strict';

	QUnit.module( 'SnakSerializer' );

	var datamodel = require( 'wikibase.datamodel' ),
		SnakSerializer = require( '../../src/Serializers/SnakSerializer.js' );

	var testSets = [
		[
			new datamodel.PropertyNoValueSnak( 'P1' ),
			{
				snaktype: 'novalue',
				property: 'P1'
			}
		], [
			new datamodel.PropertySomeValueSnak( 'P1' ),
			{
				snaktype: 'somevalue',
				property: 'P1'
			}
		], [
			new datamodel.PropertyValueSnak( 'P1', new dv.StringValue( 'some string' ) ),
			{
				snaktype: 'value',
				property: 'P1',
				datavalue: {
					type: 'string',
					value: 'some string'
				}
			}
		]
	];

	QUnit.test( 'serialize()', function( assert ) {
		assert.expect( 3 );
		var snakSerializer = new SnakSerializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.deepEqual(
				snakSerializer.serialize( testSets[i][0] ),
				testSets[i][1],
				'Test set #' + i + ': Serializing successful.'
			);
		}
	} );

}( dataValues ) );
