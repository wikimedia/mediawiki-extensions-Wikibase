/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'MultiTermMapDeserializer' );
	var MultiTermMapDeserializer = require( '../../src/Deserializers/MultiTermMapDeserializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	var testSets = [
		[
			{},
			new datamodel.MultiTermMap()
		], [
			{
				en: [ { language: 'en', value: 'en-test' } ],
				de: [ { language: 'de', value: 'de-test' } ]
			},
			new datamodel.MultiTermMap( {
				en: new datamodel.MultiTerm( 'en', [ 'en-test' ] ),
				de: new datamodel.MultiTerm( 'de', [ 'de-test' ] )
			} )
		], [
			{
				en: [ { language: 'en', value: 'en-test' } ],
				de: [ { language: 'en', value: 'en-test' } ]
			},
			new datamodel.MultiTermMap( {
				en: new datamodel.MultiTerm( 'en', [ 'en-test' ] ),
				de: new datamodel.MultiTerm( 'en', [ 'en-test' ] )
			} )
		]
	];

	QUnit.test( 'deserialize()', function( assert ) {
		assert.expect( 3 );
		var multiTermMapDeserializer = new MultiTermMapDeserializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.deepEqual(
				multiTermMapDeserializer.deserialize( testSets[i][0] ),
				testSets[i][1],
				'Test set #' + i + ': Deserializing successful.'
			);
		}
	} );

}() );
