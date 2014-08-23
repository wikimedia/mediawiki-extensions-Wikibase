/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.SiteLinkSerializer' );

var testSets = [
	[
		new wb.datamodel.SiteLink( 'site-id', 'page-title', ['badge-1', 'badge-2'] ),
		{
			site: 'site-id',
			title: 'page-title',
			badges: ['badge-1', 'badge-2']
		}
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var siteLinkSerializer = new wb.serialization.SiteLinkSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			siteLinkSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
