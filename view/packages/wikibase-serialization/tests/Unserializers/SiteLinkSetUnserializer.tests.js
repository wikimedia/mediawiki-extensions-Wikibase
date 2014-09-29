/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.SiteLinkSetUnserializer' );

var testSets = [
	[
		{},
		new wb.datamodel.SiteLinkSet()
	], [
		{
			site: {
				site: 'site',
				title: 'page',
				badges: []
			}
		},
		new wb.datamodel.SiteLinkSet( [new wb.datamodel.SiteLink( 'site', 'page' )] )
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var siteLinkSetUnserializer = new wb.serialization.SiteLinkSetUnserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			siteLinkSetUnserializer.unserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Unserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
