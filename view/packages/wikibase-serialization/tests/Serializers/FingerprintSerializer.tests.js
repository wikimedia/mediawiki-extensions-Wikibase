/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.FingerprintSerializer' );

var testSets = [
	[
		new wb.datamodel.Fingerprint(),
		{
			labels: {},
			descriptions: {},
			aliases: {}
		}
	], [
		new wb.datamodel.Fingerprint(
			new wb.datamodel.TermMap( { en: new wb.datamodel.Term( 'en', 'label' ) } ),
			new wb.datamodel.TermMap( { en: new wb.datamodel.Term( 'en', 'description' ) } ),
			new wb.datamodel.MultiTermMap( { en: new wb.datamodel.MultiTerm( 'en', ['alias'] ) } )
		),
		{
			labels: { en: { language: 'en', value: 'label' } },
			descriptions: { en: { language: 'en', value: 'description' } },
			aliases: { en: [{ language: 'en', value: 'alias' }] }
		}
	]
];

QUnit.test( 'serialize()', function( assert ) {
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
