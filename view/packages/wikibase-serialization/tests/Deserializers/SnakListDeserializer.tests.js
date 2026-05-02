/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */

( function() {
	'use strict';

	var SnakListDeserializer = require( '../../src/Deserializers/SnakListDeserializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	QUnit.module( 'SnakListDeserializer' );

	var testSets = [
		[
			[
				{},
				undefined
			],
			new datamodel.SnakList()
		], [
			[
				{
					P1: [ {
						snaktype: 'novalue',
						property: 'P1'
					} ]
				},
				undefined
			],
			new datamodel.SnakList( [ new datamodel.PropertyNoValueSnak( 'P1' ) ] )
		], [
			[
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
				},
				[ 'P2', 'P1' ]
			],
			new datamodel.SnakList( [
				new datamodel.PropertyNoValueSnak( 'P2' ),
				new datamodel.PropertyNoValueSnak( 'P1' ),
				new datamodel.PropertySomeValueSnak( 'P1' )
			] )
		]
	];

	QUnit.test( 'deserialize()', function( assert ) {
		assert.expect( 3 );
		var snakListDeserializer = new SnakListDeserializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.ok(
				snakListDeserializer.deserialize( testSets[i][0][0], testSets[i][0][1] )
					.equals( testSets[i][1] ),
				'Test set #' + i + ': Deserializing successful.'
			);
		}
	} );

}() );
