/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.SiteLinkSetDeserializer' );

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

QUnit.test( 'deserialize()', function( assert ) {
	var siteLinkSetDeserializer = new wb.serialization.SiteLinkSetDeserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			siteLinkSetDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Deserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
