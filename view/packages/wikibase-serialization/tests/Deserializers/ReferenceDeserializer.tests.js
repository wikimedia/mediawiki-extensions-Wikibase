/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	var ReferenceDeserializer = require( '../../src/Deserializers/ReferenceDeserializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	QUnit.module( 'ReferenceDeserializer' );

	var testSets = [
		[
			{
				hash: 'i am a hash',
				snaks: {
					P1: [ {
						snaktype: 'novalue',
						property: 'P1'
					} ]
				},
				'snaks-order': [ 'P1' ]
			},
			new datamodel.Reference(
				new datamodel.SnakList( [ new datamodel.PropertyNoValueSnak( 'P1' ) ] ),
				'i am a hash'
			)
		], [
			{
				hash: 'i am a hash',
				snaks: {
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
				'snaks-order': [ 'P2', 'P1' ]
			},
			new datamodel.Reference(
				new datamodel.SnakList( [
					new datamodel.PropertyNoValueSnak( 'P2' ),
					new datamodel.PropertyNoValueSnak( 'P1' ),
					new datamodel.PropertySomeValueSnak( 'P1' )
				] ),
				'i am a hash'
			)
		]
	];

	QUnit.test( 'deserialize()', function( assert ) {
		assert.expect( 2 );
		var referenceDeserializer = new ReferenceDeserializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.ok(
				referenceDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
				'Test set #' + i + ': Deserializing successful.'
			);
		}
	} );

}() );
