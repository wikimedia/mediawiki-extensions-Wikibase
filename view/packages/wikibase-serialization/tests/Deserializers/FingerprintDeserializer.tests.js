/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'FingerprintDeserializer' );
	var FingerprintDeserializer = require( '../../src/Deserializers/FingerprintDeserializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	var testSets = [
		[
			{
				labels: {},
				descriptions: {},
				aliases: {}
			},
			new datamodel.Fingerprint()
		], [
			{
				labels: { en: { language: 'en', value: 'label' } },
				descriptions: { en: { language: 'en', value: 'description' } },
				aliases: { en: [ { language: 'en', value: 'alias' } ] }
			},
			new datamodel.Fingerprint(
				new datamodel.TermMap( { en: new datamodel.Term( 'en', 'label' ) } ),
				new datamodel.TermMap( { en: new datamodel.Term( 'en', 'description' ) } ),
				new datamodel.MultiTermMap( { en: new datamodel.MultiTerm( 'en', [ 'alias' ] ) } )
			)
		]
	];

	QUnit.test( 'deserialize()', function( assert ) {
		assert.expect( 2 );
		var fingerprintDeserializer = new FingerprintDeserializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.ok(
				fingerprintDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
				'Test set #' + i + ': Deserializing successful.'
			);
		}
	} );

}() );
