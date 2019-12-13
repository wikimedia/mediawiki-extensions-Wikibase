/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'ReferenceListSerializer' );

	var datamodel = require( 'wikibase.datamodel' ),
		ReferenceListSerializer = require( '../../src/Serializers/ReferenceListSerializer.js' );

	var testSets = [
		[
			new datamodel.ReferenceList(),
			[]
		], [
			new datamodel.ReferenceList( [ new datamodel.Reference() ] ),
			[
				{
					snaks: {},
					'snaks-order': []
				}
			]
		], [
			new datamodel.ReferenceList( [
				new datamodel.Reference( null, 'hash1' ),
				new datamodel.Reference( null, 'hash2' )
			] ),
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
			]
		]
	];

	QUnit.test( 'serialize()', function( assert ) {
		assert.expect( 3 );
		var referenceListSerializer = new ReferenceListSerializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.deepEqual(
				referenceListSerializer.serialize( testSets[i][0] ),
				testSets[i][1],
				'Test set #' + i + ': Serializing successful.'
			);
		}
	} );

}() );
