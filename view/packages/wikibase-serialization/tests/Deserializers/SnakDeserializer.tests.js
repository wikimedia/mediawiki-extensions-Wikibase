/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, dv, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.SnakDeserializer' );

var testSets = [
	[
		{
			snaktype: 'novalue',
			property: 'P1'
		},
		new wb.datamodel.PropertyNoValueSnak( 'P1' )
	], [
		{
			snaktype: 'somevalue',
			property: 'P1'
		},
		new wb.datamodel.PropertySomeValueSnak( 'P1' )
	], [
		{
			snaktype: 'value',
			property: 'P1',
			datavalue: {
				type: 'string',
				value: 'some string'
			}
		},
		new wb.datamodel.PropertyValueSnak( 'P1', new dv.StringValue( 'some string' ) )
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
		new wb.datamodel.PropertyValueSnak( 'P1', new dv.UnDeserializableValue(
				{ foo: 'bar' },
				'string',
				'String is invalid.'
			)
		)
	]
];

QUnit.test( 'deserialize()', function( assert ) {
	assert.expect( 4 );
	var snakDeserializer = new wb.serialization.SnakDeserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			snakDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Deserializing successful.'
		);
	}
} );

}( wikibase, dataValues, QUnit ) );
