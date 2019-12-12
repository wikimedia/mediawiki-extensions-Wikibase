/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	var ReferenceListDeserializer = require( '../../src/Deserializers/ReferenceListDeserializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	QUnit.module( 'ReferenceListDeserializer' );

	var testSets = [
		[
			[],
			new datamodel.ReferenceList()
		], [
			[
				{
					snaks: {},
					'snaks-order': []
				}
			],
			new datamodel.ReferenceList( [ new datamodel.Reference() ] )
		], [
			[
				{
					snaks: {},
					'snaks-order': [],
					hash: 'hash1'
				}, {
					snaks: {},
					'snaks-order': [],
					hash: 'hash2'
				}
			],
			new datamodel.ReferenceList( [
				new datamodel.Reference( null, 'hash1' ),
				new datamodel.Reference( null, 'hash2' )
			] )
		]
	];

	QUnit.test( 'deserialize()', function( assert ) {
		assert.expect( 3 );
		var referenceListDeserializer = new ReferenceListDeserializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.ok(
				referenceListDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
				'Test set #' + i + ': Deserializing successful.'
			);
		}
	} );

}() );
