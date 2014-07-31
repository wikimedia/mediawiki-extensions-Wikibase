/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, $, QUnit ) {
'use strict';

var siteDetails = {
	apiUrl: 'http://en.wikipedia.org/w/api.php',
	id: 'en-global',
	name: 'English Wikipedia',
	pageUrl: 'http://en.wikipedia.org/wiki/$1',
	shortName: 'English',
	languageCode: 'en',
	group: 'wikipedia'
};

var site = new wb.datamodel.Site( siteDetails ),
	pageName = 'test';

QUnit.module( 'wikibase.datamodel.SiteLink', QUnit.newMwEnvironment() );

QUnit.test( 'Basic tests', function( assert ) {
	var siteLink = new wb.datamodel.SiteLink( site, pageName );

	assert.ok(
		siteLink.getSite() instanceof wb.datamodel.Site,
		'Verified site instance.'
	);

	assert.equal(
		siteLink.getPageName(),
		pageName,
		'Verified page name.'
	);
} );

QUnit.test( 'Link generation', function( assert ) {
	var siteLink = new wb.datamodel.SiteLink( site, pageName );

	assert.equal(
		siteLink.getUrl(),
		'http://en.wikipedia.org/wiki/test',
		'Created URL.'
	);

	var $link = siteLink.getLink();

	assert.ok(
		$link.length === 1 && $link instanceof $ && $link.get( 0 ).nodeName === 'A',
		'Created HTML link.'
	);
} );

QUnit.test( 'Badges', function( assert ) {
	var siteLink = new wb.datamodel.SiteLink( site, pageName ),
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

	siteLink = new wb.datamodel.SiteLink( site, pageName, badges );

	assert.equal(
		badges.join( ',' ),
		siteLink.getBadges().join( ',' ),
		'Instantiated site link with badges.'
	);
} );

}( wikibase, jQuery, QUnit ) );
