/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.FingerprintSerializer' );

var datamodel = require( 'wikibase.datamodel' );

var testSets = [
	[
		new datamodel.Fingerprint(),
		{
			labels: {},
			descriptions: {},
			aliases: {}
		}
	], [
		new datamodel.Fingerprint(
			new datamodel.TermMap( { en: new datamodel.Term( 'en', 'label' ) } ),
			new datamodel.TermMap( { en: new datamodel.Term( 'en', 'description' ) } ),
			new datamodel.MultiTermMap( { en: new datamodel.MultiTerm( 'en', [ 'alias' ] ) } )
		),
		{
			labels: { en: { language: 'en', value: 'label' } },
			descriptions: { en: { language: 'en', value: 'description' } },
			aliases: { en: [ { language: 'en', value: 'alias' } ] }
		}
	]
];

QUnit.test( 'serialize()', function( assert ) {
	assert.expect( 2 );
	var fingerprintSerializer = new wb.serialization.FingerprintSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			fingerprintSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
