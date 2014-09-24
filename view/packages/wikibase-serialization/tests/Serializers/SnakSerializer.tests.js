/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, dv, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.SnakSerializer' );

var testSets = [
	[
		new wb.datamodel.PropertyNoValueSnak( 'P1' ),
		{
			snaktype: 'novalue',
			property: 'P1'
		}
	], [
		new wb.datamodel.PropertySomeValueSnak( 'P1' ),
		{
			snaktype: 'somevalue',
			property: 'P1'
		}
	], [
		new wb.datamodel.PropertyValueSnak( 'P1', new dv.StringValue( 'some string' ) ),
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
	var snakSerializer = new wb.serialization.SnakSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			snakSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( wikibase, dataValues, QUnit ) );
