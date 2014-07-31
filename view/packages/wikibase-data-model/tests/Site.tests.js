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

var site = new wb.datamodel.Site( siteDetails );

QUnit.module( 'wikibase.datamodel.Site', QUnit.newMwEnvironment() );

QUnit.test( 'Basic checks', function( assert ) {

	assert.equal(
		site.getId(),
		siteDetails.id,
		'verified site id'
	);

	assert.equal(
		site.getName(),
		siteDetails.name,
		'verified site name'
	);

	assert.equal(
		site.getShortName(),
		siteDetails.shortName,
		'verified short site name'
	);

	assert.equal(
		site.getPageUrl(),
		siteDetails.pageUrl,
		'verified page url'
	);

	assert.equal(
		site.getApi(),
		siteDetails.apiUrl,
		'verified site api'
	);

	assert.equal(
		site.getLanguageCode(),
		'en',
		'retrieved language code'
	);

	assert.equal(
		site.getGroup(),
		siteDetails.group,
		'verified site group'
	);
} );

}( wikibase, jQuery, QUnit ) );
