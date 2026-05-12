/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'TermMapSerializer' );

	var datamodel = require( 'wikibase.datamodel' ),
		TermMapSerializer = require( '../../src/Serializers/TermMapSerializer.js' );

	var testSets = [
		[
			new datamodel.TermMap(),
			{}
		], [
			new datamodel.TermMap( {
				en: new datamodel.Term( 'en', 'en-test' ),
				de: new datamodel.Term( 'de', 'de-test' )
			} ),
			{
				en: { language: 'en', value: 'en-test' },
				de: { language: 'de', value: 'de-test' }
			}
		], [
			new datamodel.TermMap( {
				en: new datamodel.Term( 'en', 'en-test' ),
				de: new datamodel.Term( 'en', 'en-test' )
			} ),
			{
				en: { language: 'en', value: 'en-test' },
				de: { language: 'en', value: 'en-test' }
			}
		]
	];

	QUnit.test( 'serialize()', function( assert ) {
		assert.expect( 3 );
		var termMapSerializer = new TermMapSerializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.deepEqual(
				termMapSerializer.serialize( testSets[i][0] ),
				testSets[i][1],
				'Test set #' + i + ': Serializing successful.'
			);
		}
	} );

}() );
