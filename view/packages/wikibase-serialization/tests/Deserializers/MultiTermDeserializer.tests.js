/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'MultiTermDeserializer' );
	var MultiTermDeserializer = require( '../../src/Deserializers/MultiTermDeserializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	var testSets = [
		[
			[ { language: 'en', value: 'test1' }, { language: 'en', value: 'test2' } ],
			new datamodel.MultiTerm( 'en', [ 'test1', 'test2' ] )
		]
	];

	QUnit.test( 'deserialize()', function( assert ) {
		assert.expect( 1 );
		var multiTermDeserializer = new MultiTermDeserializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.deepEqual(
				multiTermDeserializer.deserialize( testSets[i][0] ),
				testSets[i][1],
				'Test set #' + i + ': Deserializing successful.'
			);
		}
	} );

}() );
