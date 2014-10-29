/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.SnakListSerializer' );

var testSets = [
	[
		new wb.datamodel.SnakList(),
		{}
	], [
		new wb.datamodel.SnakList( [new wb.datamodel.PropertyNoValueSnak( 'P1' )] ),
		{
			P1: [{
				snaktype: 'novalue',
				property: 'P1'
			}]
		}
	], [
		new wb.datamodel.SnakList( [
			new wb.datamodel.PropertyNoValueSnak( 'P2' ),
			new wb.datamodel.PropertyNoValueSnak( 'P1' ),
			new wb.datamodel.PropertySomeValueSnak( 'P1' )
		] ),
		{
			P1: [{
				snaktype: 'novalue',
				property: 'P1'
			}, {
				snaktype: 'somevalue',
				property: 'P1'
			}],
			P2: [{
				snaktype: 'novalue',
				property: 'P2'
			}]
		}
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var snakListSerializer = new wb.serialization.SnakListSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			snakListSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
