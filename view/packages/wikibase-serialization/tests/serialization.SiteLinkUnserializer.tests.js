/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.SiteLinkUnserializer' );

var testSets = [
	[
		{
			site: 'site-id',
			title: 'page-title',
			badges: ['badge-1', 'badge-2']
		},
		new wb.datamodel.SiteLink( 'site-id', 'page-title', ['badge-1', 'badge-2'] )
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var siteLinkUnserializer = new wb.serialization.SiteLinkUnserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			siteLinkUnserializer.unserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Unserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
