/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.SiteLinkList' );

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
			( new wb.datamodel.SiteLinkList( testSets[i] ) ) instanceof wb.datamodel.SiteLinkList,
			'Instantiated SiteLinkList.'
		);
	}

	assert.throws(
		function() {
			return new wb.datamodel.SiteLinkList( ['string1', 'string2'] );
		},
		'Throwing error when trying to instantiate a SiteLinkList with other than SiteLink objects.'
	);
} );

}( wikibase, QUnit ) );
