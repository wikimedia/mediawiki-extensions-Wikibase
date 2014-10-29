/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.SiteLinkSet' );

var testSets = [
	[],
	[
		new wb.datamodel.SiteLink( 'de', 'de-page' ),
		new wb.datamodel.SiteLink( 'en', 'en-page' )
	]
];

QUnit.test( 'Constructor', function( assert ) {
	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			( new wb.datamodel.SiteLinkSet( testSets[i] ) ) instanceof wb.datamodel.SiteLinkSet,
			'Test set #' + i + ': Instantiated SiteLinkSet.'
		);
	}
} );

}( wikibase, QUnit ) );
