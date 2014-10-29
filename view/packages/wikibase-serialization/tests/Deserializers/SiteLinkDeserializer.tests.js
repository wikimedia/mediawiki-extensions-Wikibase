/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.SiteLinkDeserializer' );

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

QUnit.test( 'deserialize()', function( assert ) {
	var siteLinkDeserializer = new wb.serialization.SiteLinkDeserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			siteLinkDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Deserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
