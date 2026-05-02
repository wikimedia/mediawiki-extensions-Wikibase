/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'TermMapDeserializer' );

	var datamodel = require( 'wikibase.datamodel' ),
		TermMapDeserializer = require( '../../src/Deserializers/TermMapDeserializer.js' );

	var testSets = [
		[
			{},
			new datamodel.TermMap()
		], [
			{
				en: { language: 'en', value: 'en-test' },
				de: { language: 'de', value: 'de-test' }
			},
			new datamodel.TermMap( {
				en: new datamodel.Term( 'en', 'en-test' ),
				de: new datamodel.Term( 'de', 'de-test' )
			} )
		], [
			{
				en: { language: 'en', value: 'en-test' },
				de: { language: 'en', value: 'en-test' }
			},
			new datamodel.TermMap( {
				en: new datamodel.Term( 'en', 'en-test' ),
				de: new datamodel.Term( 'en', 'en-test' )
			} )
		]
	];

	QUnit.test( 'deserialize()', function( assert ) {
		assert.expect( 3 );
		var termMapDeserializer = new TermMapDeserializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.deepEqual(
				termMapDeserializer.deserialize( testSets[i][0] ),
				testSets[i][1],
				'Test set #' + i + ': Deserializing successful.'
			);
		}
	} );

}() );
