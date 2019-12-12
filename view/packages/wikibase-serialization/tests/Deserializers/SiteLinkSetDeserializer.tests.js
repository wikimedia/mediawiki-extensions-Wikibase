/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'SiteLinkSetDeserializer' );
	var SiteLinkSetDeserializer = require( '../../src/Deserializers/SiteLinkSetDeserializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	var testSets = [
		[
			{},
			new datamodel.SiteLinkSet()
		], [
			{
				site: {
					site: 'site',
					title: 'page',
					badges: []
				}
			},
			new datamodel.SiteLinkSet( [ new datamodel.SiteLink( 'site', 'page' ) ] )
		]
	];

	QUnit.test( 'deserialize()', function( assert ) {
		assert.expect( 2 );
		var siteLinkSetDeserializer = new SiteLinkSetDeserializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.ok(
				siteLinkSetDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
				'Test set #' + i + ': Deserializing successful.'
			);
		}
	} );

}() );
