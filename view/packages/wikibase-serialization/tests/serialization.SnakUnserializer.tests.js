/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, dv, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.SnakUnserializer' );

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
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var snakUnserializer = new wb.serialization.SnakUnserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			snakUnserializer.unserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Unserializing successful.'
		);
	}
} );

}( wikibase, dataValues, QUnit ) );
