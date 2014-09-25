/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.FingerprintUnserializer' );

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
			new wb.datamodel.TermList( [new wb.datamodel.Term( 'en', 'label' )] ),
			new wb.datamodel.TermList( [new wb.datamodel.Term( 'en', 'description' )] ),
			new wb.datamodel.TermGroupList( [new wb.datamodel.TermGroup( 'en', ['alias'] )] )
		)
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var fingerprintUnserializer = new wb.serialization.FingerprintUnserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			fingerprintUnserializer.unserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Unserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
