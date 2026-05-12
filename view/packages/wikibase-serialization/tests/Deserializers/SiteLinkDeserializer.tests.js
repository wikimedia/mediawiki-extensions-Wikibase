/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'SiteLinkDeserializer' );
	var SiteLinkDeserializer = require( '../../src/Deserializers/SiteLinkDeserializer.js' ),
		datamodel = require( 'wikibase.datamodel' );

	var testSets = [
		[
			{
				site: 'site-id',
				title: 'page-title',
				badges: [ 'badge-1', 'badge-2' ]
			},
			new datamodel.SiteLink( 'site-id', 'page-title', [ 'badge-1', 'badge-2' ] )
		]
	];

	QUnit.test( 'deserialize()', function( assert ) {
		assert.expect( 1 );
		var siteLinkDeserializer = new SiteLinkDeserializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.ok(
				siteLinkDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
				'Test set #' + i + ': Deserializing successful.'
			);
		}
	} );

}() );
