/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'TermDeserializer' );

	var datamodel = require( 'wikibase.datamodel' ),
		TermDeserializer = require( '../../src/Deserializers/TermDeserializer.js' );

	var testSets = [
		[
			{ language: 'en', value: 'test' },
			new datamodel.Term( 'en', 'test' )
		]
	];

	QUnit.test( 'deserialize()', function( assert ) {
		assert.expect( 1 );
		var termDeserializer = new TermDeserializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.deepEqual(
				termDeserializer.deserialize( testSets[i][0] ),
				testSets[i][1],
				'Test set #' + i + ': Deserializing successful.'
			);
		}
	} );

}() );
