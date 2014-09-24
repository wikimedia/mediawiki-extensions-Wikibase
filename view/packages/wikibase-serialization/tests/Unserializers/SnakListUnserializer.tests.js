/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.SnakListUnserializer' );

var testSets = [
	[
		[
			{},
			undefined
		],
		new wb.datamodel.SnakList()
	], [
		[
			{
				P1: [{
					snaktype: 'novalue',
					property: 'P1'
				}]
			},
			undefined
		],
		new wb.datamodel.SnakList( [new wb.datamodel.PropertyNoValueSnak( 'P1' )] )
	], [
		[
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
			},
			['P2', 'P1']
		],
		new wb.datamodel.SnakList( [
			new wb.datamodel.PropertyNoValueSnak( 'P2' ),
			new wb.datamodel.PropertyNoValueSnak( 'P1' ),
			new wb.datamodel.PropertySomeValueSnak( 'P1' )
		] )
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var snakListUnserializer = new wb.serialization.SnakListUnserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			snakListUnserializer.unserialize( testSets[i][0][0], testSets[i][0][1] )
				.equals( testSets[i][1] ),
			'Test set #' + i + ': Unserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
