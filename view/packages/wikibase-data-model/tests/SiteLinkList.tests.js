/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.SiteLinkList' );

function getDefaultSiteLinkList() {
	return new wb.datamodel.SiteLinkList( [
		new wb.datamodel.SiteLink( 'de', 'de-page' ),
		new wb.datamodel.SiteLink( 'en', 'en-page' )
	] );
}

QUnit.test( 'Constructor', function( assert ) {
	assert.ok(
		getDefaultSiteLinkList() instanceof wb.datamodel.SiteLinkList,
		'Instantiated SiteLinkList.'
	);

	assert.throws(
		function() {
			return new wb.datamodel.SiteLinkList( ['string1', 'string2'] );
		},
		'Throwing error when trying to instantiate SiteLinkList with other than SiteLink objects.'
	);

	assert.throws(
		function() {
			return new wb.datamodel.SiteLinkList( [
				new wb.datamodel.SiteLink( 'de', 'de-page1' ),
				new wb.datamodel.SiteLink( 'de', 'de-page2' )
			] );
		},
		'Throwing error when trying to instantiate a SiteLinkList with multiple SiteLink objects '
		+ 'for one site id.'
	);
} );

QUnit.test( 'getBySiteId()', function( assert ) {
	assert.ok(
		getDefaultSiteLinkList().getBySiteId( 'en' ).equals(
			new wb.datamodel.SiteLink( 'en', 'en-page' )
		),
		'Retrieved SiteLink object by site id.'
	);

	assert.strictEqual(
		getDefaultSiteLinkList().getBySiteId( 'does-not-exist' ),
		null,
		'Returning NULL when no SiteLink object is set for a particular site id.'
	);
} );

QUnit.test( 'removeBySiteId() & length attribute', function( assert ) {
	var siteLinkList = getDefaultSiteLinkList();

	assert.equal(
		siteLinkList.length,
		2,
		'SiteLinkList contains 2 SiteLink objects.'
	);

	siteLinkList.removeBySiteId( 'de' );

	assert.strictEqual(
		siteLinkList.getBySiteId( 'de' ),
		null,
		'Removed SiteLink.'
	);

	assert.strictEqual(
		siteLinkList.length,
		1,
		'SiteLinkList contains 1 SiteLink object.'
	);

	siteLinkList.removeBySiteId( 'does-not-exist' );

	assert.strictEqual(
		siteLinkList.length,
		1,
		'SiteLinkList contains 1 SiteLink object after trying to remove a SiteLink that is not '
		+ 'set.'
	);

	siteLinkList.removeBySiteId( 'en' );

	assert.strictEqual(
		siteLinkList.getBySiteId( 'en' ),
		null,
		'Removed SiteLink.'
	);

	assert.strictEqual(
		siteLinkList.length,
		0,
		'SiteLinkList is empty.'
	);
} );

QUnit.test( 'hasSiteLink()', function( assert ) {
	assert.ok(
		getDefaultSiteLinkList().hasSiteLink( new wb.datamodel.SiteLink( 'de', 'de-page' ) ),
		'Verified hasSiteLink() returning TRUE.'
	);

	assert.ok(
		!getDefaultSiteLinkList().hasSiteLink(
			new wb.datamodel.SiteLink( 'de', 'does-not-exist' )
		),
		'Verified hasSiteLink() returning FALSE.'
	);

	assert.throws(
		function() {
			getDefaultSiteLinkList().hasSiteLink( 'de-page' );
		},
		'Throwing error when submitting a plain string.'
	);
} );

QUnit.test( 'setSiteLink() & length attribute', function( assert ) {
	var siteLinkList = getDefaultSiteLinkList(),
		newEnSiteLink = new wb.datamodel.SiteLink( 'en', 'en-page-overwritten' ),
		newSiteLink = new wb.datamodel.SiteLink( 'ar', 'ar-page' );

	assert.ok(
		siteLinkList.length,
		2,
		'SiteLinkList contains 2 SiteLink objects.'
	);

	siteLinkList.setSiteLink( newEnSiteLink );

	assert.ok(
		siteLinkList.getBySiteId( 'en' ).equals( newEnSiteLink ),
		'Set new "en" SiteLink.'
	);

	assert.equal(
		siteLinkList.length,
		2,
		'Length remains unchanged when overwriting a SiteLink.'
	);

	siteLinkList.setSiteLink( newSiteLink );

	assert.ok(
		siteLinkList.getBySiteId( 'ar' ).equals( newSiteLink ),
		'Added new SiteLink.'
	);

	assert.equal(
		siteLinkList.length,
		3,
		'Increased length when adding new SiteLink.'
	);

	assert.throws(
		function() {
			siteLinkList.setSiteLink( 'string' );
		},
		'Throwing error when trying to set a plain string.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	var siteLinkList = getDefaultSiteLinkList();

	assert.ok(
		siteLinkList.equals( getDefaultSiteLinkList() ),
		'Verified equals() retuning TRUE.'
	);

	siteLinkList.setSiteLink( new wb.datamodel.SiteLink( 'en', 'en-page-overwritten' ) );

	assert.ok(
		!siteLinkList.equals( getDefaultSiteLinkList() ),
		'FALSE when a SiteLink has been overwritten.'
	);

	siteLinkList = getDefaultSiteLinkList();
	siteLinkList.removeBySiteId( 'en' );

	assert.ok(
		!siteLinkList.equals( getDefaultSiteLinkList() ),
		'FALSE when a SiteLink has been removed.'
	);

	assert.ok(
		!siteLinkList.equals( [
			getDefaultSiteLinkList().getBySiteId( 'de' ),
			getDefaultSiteLinkList().getBySiteId( 'en' )
		] ),
		'FALSE when submitting an array instead of a SiteLinkList instance.'
	);
} );

}( wikibase, jQuery, QUnit ) );
