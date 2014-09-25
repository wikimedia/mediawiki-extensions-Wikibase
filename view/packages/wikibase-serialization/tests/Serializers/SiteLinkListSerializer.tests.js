/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.SiteLinkListSerializer' );

var testSets = [
	[
		new wb.datamodel.SiteLinkList(),
		{}
	], [
		new wb.datamodel.SiteLinkList( [new wb.datamodel.SiteLink( 'site', 'page' )] ),
		{
			site: {
				site: 'site',
				title: 'page',
				badges: []
			}
		}
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var siteLinkListSerializer = new wb.serialization.SiteLinkListSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			siteLinkListSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
