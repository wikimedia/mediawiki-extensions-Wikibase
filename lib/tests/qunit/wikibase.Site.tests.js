/**
 * QUnit tests for site component
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */

( function( mw, wb, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'wikibase.Site', QUnit.newWbEnvironment( {
		setup: function() {
			this.siteDetails = {
				apiUrl: 'http://en.wikipedia.org/w/api.php',
				id: 'en-global',
				name: 'English Wikipedia',
				pageUrl: 'http://en.wikipedia.org/wiki/$1',
				shortName: 'English',
				languageCode: 'en',
				group: 'wikipedia'
			};
			this.string = 'test';
			this.site = new wb.Site( this.siteDetails );
		},
		teardown: function() {
			delete( this.siteDetails );
			delete( this.string );
			delete( this.site );
		}
	} ) );

	QUnit.test( 'check init', function( assert ) {
		assert.strictEqual(
			this.site._siteDetails,
			this.siteDetails,
			'set site details'
		);

		assert.strictEqual(
			this.site.getId(),
			this.siteDetails.id,
			'verified site id'
		);

		assert.strictEqual(
			this.site.getGlobalSiteId(), // deprecated, equivalent to getId() now
			this.site.getId(),
			'verified global site id'
		);

		assert.strictEqual(
			this.site.getName(),
			this.siteDetails.name,
			'verified site name'
		);

		assert.strictEqual(
			this.site.getShortName(),
			this.siteDetails.shortName,
			'verified short site name'
		);

		assert.strictEqual(
			this.site.getApi(),
			this.siteDetails.apiUrl,
			'verified site api'
		);

		assert.strictEqual(
			this.site.getGroup(),
			this.siteDetails.group,
			'verified site group'
		);
	} );

	QUnit.test( 'link handling', function( assert ) {

		assert.equal(
			this.site.getLinkTo( this.string )[0].nodeName,
			'A',
			'created DOM node for link'
		);

	} );

	QUnit.test( 'language functions', function( assert ) {

		assert.equal(
			this.site.getLanguageCode(),
			'en',
			'retrieved language code'
		);

		assert.equal(
			this.site.getLanguage().dir,
			( $.uls !== undefined ) ? 'ltr' : 'auto',
			'retrieved ltr language direction'
		);

		this.site._siteDetails.languageCode = 'ar';

		assert.equal(
			this.site.getLanguage().dir,
			( $.uls !== undefined ) ? 'rtl' : 'auto',
			'retrieved rtl language direction'
		);

		this.site._siteDetails.languageCode = 'non-existing-code';

		assert.equal(
			this.site.getLanguage().dir,
			'auto',
			'received "auto" when no special language direction could be retrieved'
		);

	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
