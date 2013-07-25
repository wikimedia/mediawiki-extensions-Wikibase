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

( function( mw, wb, GenericSet, Site, $, QUnit ) {
	'use strict';

	/**
	 * Place for all test related Objects.
	 * @type Object
	 */
	wb.tests = {};

	/**
	 * All site groups used in site definitions in TEST_SITE_DEFINITIONS.
	 * @type String[]
	 */
	var TEST_SITE_GROUPS = [ 'wikipedia', 'foo bar group' ];

	/**
	 * Site definition loaded to mw.config var "wbSiteDetails" before each test.
	 * @type Object
	 */
	var TEST_SITE_DEFINITIONS = {
		nnwiki: {
			apiUrl: '//nn.wikipedia.org/w/api.php',
			id: 'nnwiki',
			group: TEST_SITE_GROUPS[0],
			languageCode: 'nn',
			name: null,
			pageUrl: '//nn.wikipedia.org/wiki/$1',
			shortName: 'norsk nynorsk'
		},
		'foo-site': {
			apiUrl: '//foo.site.bar/api.php',
			id: 'foo-site',
			group: TEST_SITE_GROUPS[1],
			languageCode: 'foo',
			name: null,
			pageUrl: '//foo.site.bar/pages/$1',
			shortName: 'foo site'
		},
		siteInExistingGroup: {
			apiUrl: '//someSite/api.php',
			globalSiteId: 'site-in-existing-group',
			group: TEST_SITE_GROUPS[1],
			id: 'siteInExistingGroup',
			languageCode: 'siteInExistingGroup',
			name: null,
			pageUrl: '//someSite/pages/$1',
			shortName: 'some site'
		}
	};

	var NUMBER_OF_TEST_SITES = ( function() {
		var i = 0;
		for( var j in TEST_SITE_DEFINITIONS ) {
			i++;
		}
		return i;
	}() );

	QUnit.module( 'wikibase', QUnit.newWbEnvironment( {
		config: {
			wbSiteDetails: TEST_SITE_DEFINITIONS
		}
	} ) );

	QUnit.test( 'basic', 1, function( assert ) {
		assert.ok(
			wb instanceof Object,
			'initiated wikibase object'
		);
	} );

	QUnit.test( 'wikibase.getSites()', function( assert ) {
		var sites = wb.getSites();

		GenericSet.requireInstanceOfType( sites, Site );
		assert.ok(
			true,
			'getSites() returns a GenericSet of type wb.Site'
		);

		assert.strictEqual(
			sites.length,
			NUMBER_OF_TEST_SITES,
			'getSites() returns expected number of sites'
		);
	} );

	QUnit.test( 'wikibase.getSitesOfGroup()', function( assert ) {
		$.each( TEST_SITE_GROUPS, function( i, group ) {
			var sites = wb.getSitesOfGroup( group );

			GenericSet.requireInstanceOfType( sites, Site );
			assert.ok(
				true,
				'getSitesOfGroup() returns a GenericSet of type wb.Site'
			);

			var allFromRightGroup = true;
			$.each( sites, function( i, site ) {
				allFromRightGroup = allFromRightGroup && ( site.getGroup() === group );
			} );

			assert.ok(
				allFromRightGroup,
				'getSitesOfGroup( "' + group + '" ) only returned sites of group "' + group + '"'
			);
		} );
	} );

	QUnit.test( 'wikibase.getSite()', 2, function( assert ) {
		assert.ok(
			wb.getSite( 'nnwiki' ) instanceof Site,
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
			wb.hasSite( 'nnwiki' ),
			true,
			'trying to check for known site returns true'
		);

		assert.strictEqual(
			wb.hasSite( 'unknown-site' ),
			false,
			'trying to check for unknown site returns false'
		);
	} );

	QUnit.test( 'wikibase.getSiteGroups()', 2 + TEST_SITE_GROUPS.length, function( assert ) {
		var siteGroups = wb.getSiteGroups();

		assert.strictEqual(
			$.isArray( siteGroups ),
			true,
			'getSiteGroups() returns Array'
		);

		assert.strictEqual(
			siteGroups.length,
			TEST_SITE_GROUPS.length,
			'Number of expected groups is accurate'
		);

		$.each( TEST_SITE_GROUPS, function( i, group ) {
			assert.ok(
				$.inArray( group, siteGroups ) !== -1,
				'site group "' + group + '" is part of returned site groups'
			);
		} );
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

}( mediaWiki, wikibase, GenericSet, wikibase.Site, jQuery, QUnit ) );
