/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, $, QUnit ) {
'use strict';

var siteId = 'test-id',
	pageName = 'test-page';

QUnit.module( 'wikibase.datamodel.SiteLink', QUnit.newMwEnvironment() );

QUnit.test( 'Basic tests', function( assert ) {
	var siteLink = new wb.datamodel.SiteLink( siteId, pageName );

	assert.equal(
		siteLink.getSiteId(),
		'test-id',
		'Verified site id.'
	);

	assert.equal(
		siteLink.getPageName(),
		pageName,
		'Verified page name.'
	);
} );

QUnit.test( 'Badges', function( assert ) {
	var siteLink = new wb.datamodel.SiteLink( siteId, pageName ),
		badges = ['Q123', 'Q456'];

	assert.equal(
		siteLink.getBadges().length,
		0,
		'Instantiated site link with no badges.'
	);

	siteLink.setBadges( badges );

	assert.equal(
		badges.join( ',' ),
		siteLink.getBadges().join( ',' ),
		'Set badges.'
	);

	siteLink.setBadges();

	assert.equal(
		siteLink.getBadges().length,
		0,
		'Removed badges.'
	);

	siteLink = new wb.datamodel.SiteLink( siteId, pageName, badges );

	assert.equal(
		badges.join( ',' ),
		siteLink.getBadges().join( ',' ),
		'Instantiated site link with badges.'
	);
} );

}( wikibase, jQuery, QUnit ) );
