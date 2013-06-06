/**
 * QUnit tests for general wikibase JavaScript code
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

( function( mw, wb, Site, $, QUnit ) {
	'use strict';

	/**
	 * Place for all test related Objects.
	 * @type Object
	 */
	wb.tests = {};

	var sitesDefinition = {
		nn: {
			apiUrl: '//nn.wikipedia.org/w/api.php',
			globalSiteId: 'nnwiki',
			group: 'wikipedia',
			id: 'nn',
			languageCode: 'nn',
			name: null,
			pageUrl: '//nn.wikipedia.org/wiki/$1',
			shortName: 'norsk nynorsk'
		}
	};

	QUnit.module( 'wikibase', QUnit.newWbEnvironment( {
		config: {
			wbSiteDetails: sitesDefinition
		}
	} ) );

	QUnit.test( 'basic', 1, function( assert ) {
		assert.ok(
			wb instanceof Object,
			'initiated wikibase object'
		);
	} );

	QUnit.test( 'wikibase.getSites()', 3, function( assert ) {
		var sites = wb.getSites();

		assert.ok(
			$.isPlainObject( sites ),
			'getSites() returns a plain object'
		);

		assert.strictEqual(
			sites.length,
			sitesDefinition.length,
			'getSites() returns expected number of sites'
		);

		var allSiteInstances = true;
		$.each( sites, function( i, site ) {
			allSiteInstances = allSiteInstances && ( site instanceof Site );
		} );

		assert.ok(
			allSiteInstances,
			'getSites() returned object fields only hold site objects'
		);
	} );

	QUnit.test( 'wikibase.getSite()', 2, function( assert ) {
		assert.ok(
			wb.getSite( 'nn' ) instanceof Site,
			'trying to get a known site by its ID returns a site object'
		);

		assert.strictEqual(
			wb.getSite( 'unknown-site' ),
			null,
			'trying to get an unknown site returns null'
		);
	} );

	QUnit.test( 'wikibase.hasSite()', 2, function( assert ) {
		assert.strictEqual(
			wb.hasSite( 'nn' ),
			true,
			'trying to check for known site returns true'
		);

		assert.strictEqual(
			wb.hasSite( 'unknown-site' ),
			false,
			'trying to check for unknown site returns false'
		);
	} );

	QUnit.test( 'wikibase.getLanguages()', 1, function( assert ) {
		assert.ok(
			$.isPlainObject( wb.getLanguages() ),
			'getLanguages() returns a plain object'
		);
	} );

	QUnit.test( 'wikibase.getLanguageNameByCode()', 2, function( assert ) {
		// TODO: Don't assume global state, control what languages are available for this test!
		if( $.uls !== undefined ) {
			assert.strictEqual(
				wb.getLanguageNameByCode( 'de' ),
				'Deutsch',
				'getLanguageNameByCode returns language name'
			);
		} else {
			assert.strictEqual(
				wb.getLanguageNameByCode( 'de' ),
				'',
				'getLanguageNameByCode returns empty string (ULS not loaded)'
			);
		}

		assert.strictEqual(
			wb.getLanguageNameByCode( 'nonexistantlanguagecode' ),
			'',
			'getLanguageNameByCode returns empty string if unknown code'
		);
	} );

}( mediaWiki, wikibase, wikibase.Site, jQuery, QUnit ) );
