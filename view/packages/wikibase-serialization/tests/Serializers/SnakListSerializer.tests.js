/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.SnakListSerializer' );

var datamodel = require( 'wikibase.datamodel' );

var testSets = [
	[
		new datamodel.SnakList(),
		{}
	], [
		new datamodel.SnakList( [ new datamodel.PropertyNoValueSnak( 'P1' ) ] ),
		{
			P1: [ {
				snaktype: 'novalue',
				property: 'P1'
			} ]
		}
	], [
		new datamodel.SnakList( [
			new datamodel.PropertyNoValueSnak( 'P2' ),
			new datamodel.PropertyNoValueSnak( 'P1' ),
			new datamodel.PropertySomeValueSnak( 'P1' )
		] ),
		{
			P1: [ {
				snaktype: 'novalue',
				property: 'P1'
			}, {
				snaktype: 'somevalue',
				property: 'P1'
			} ],
			P2: [ {
				snaktype: 'novalue',
				property: 'P2'
			} ]
		}
	]
];

QUnit.test( 'serialize()', function( assert ) {
	assert.expect( 3 );
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
