/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.ReferenceUnserializer' );

var testSets = [
	[
		{
			hash: 'i am a hash',
			snaks: {
				P1: [{
					snaktype: 'novalue',
					property: 'P1'
				}]
			},
			'snaks-order': ['P1']
		},
		new wb.datamodel.Reference(
			new wb.datamodel.SnakList( [new wb.datamodel.PropertyNoValueSnak( 'P1' )] ),
			'i am a hash'
		)
	], [
		{
			hash: 'i am a hash',
			snaks: {
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
			'snaks-order': ['P2', 'P1']
		},
		new wb.datamodel.Reference(
			new wb.datamodel.SnakList( [
				new wb.datamodel.PropertyNoValueSnak( 'P2' ),
				new wb.datamodel.PropertyNoValueSnak( 'P1' ),
				new wb.datamodel.PropertySomeValueSnak( 'P1' )
			] ),
			'i am a hash'
		)
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var referenceUnserializer = new wb.serialization.ReferenceUnserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			referenceUnserializer.unserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Unserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
