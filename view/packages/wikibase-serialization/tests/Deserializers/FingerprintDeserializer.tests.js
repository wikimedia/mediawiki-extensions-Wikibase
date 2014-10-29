/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.FingerprintDeserializer' );

var testSets = [
	[
		{
			labels: {},
			descriptions: {},
			aliases: {}
		},
		new wb.datamodel.Fingerprint()
	], [
		{
			labels: { en: { language: 'en', value: 'label' } },
			descriptions: { en: { language: 'en', value: 'description' } },
			aliases: { en: [{ language: 'en', value: 'alias' }] }
		},
		new wb.datamodel.Fingerprint(
			new wb.datamodel.TermMap( { en: new wb.datamodel.Term( 'en', 'label' ) } ),
			new wb.datamodel.TermMap( { en: new wb.datamodel.Term( 'en', 'description' ) } ),
			new wb.datamodel.MultiTermMap( { en: new wb.datamodel.MultiTerm( 'en', ['alias'] ) } )
		)
	]
];

QUnit.test( 'deserialize()', function( assert ) {
	var fingerprintDeserializer = new wb.serialization.FingerprintDeserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			fingerprintDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Deserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
