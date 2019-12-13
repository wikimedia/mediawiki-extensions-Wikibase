/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( dv ) {
	'use strict';

	QUnit.module( 'SnakDeserializer' );

	var datamodel = require( 'wikibase.datamodel' ),
		SnakDeserializer = require( '../../src/Deserializers/SnakDeserializer.js' );

	var testSets = [
		[
			{
				snaktype: 'novalue',
				property: 'P1'
			},
			new datamodel.PropertyNoValueSnak( 'P1' )
		], [
			{
				snaktype: 'somevalue',
				property: 'P1'
			},
			new datamodel.PropertySomeValueSnak( 'P1' )
		], [
			{
				snaktype: 'value',
				property: 'P1',
				datavalue: {
					type: 'string',
					value: 'some string'
				}
			},
			new datamodel.PropertyValueSnak( 'P1', new dv.StringValue( 'some string' ) )
		], [
			{
				snaktype: 'value',
				property: 'P1',
				datavalue: {
					type: 'undeserializable',
					value: {
						value: { foo: 'bar' },
						type: 'string',
						error: 'String is invalid.'
					}
				}
			},
			new datamodel.PropertyValueSnak( 'P1', new dv.UnDeserializableValue(
				{ foo: 'bar' },
				'string',
				'String is invalid.'
			) )
		]
	];

	QUnit.test( 'deserialize()', function( assert ) {
		assert.expect( 4 );
		var snakDeserializer = new SnakDeserializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.ok(
				snakDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
				'Test set #' + i + ': Deserializing successful.'
			);
		}
	} );

}( dataValues ) );
