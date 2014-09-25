/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.SiteLinkListUnserializer' );

var testSets = [
	[
		{},
		new wb.datamodel.SiteLinkList()
	], [
		{
			site: {
				site: 'site',
				title: 'page',
				badges: []
			}
		},
		new wb.datamodel.SiteLinkList( [new wb.datamodel.SiteLink( 'site', 'page' )] )
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var siteLinkListUnserializer = new wb.serialization.SiteLinkListUnserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			siteLinkListUnserializer.unserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Unserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
