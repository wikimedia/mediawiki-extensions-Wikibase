/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */

( function ( wb, Site, $, QUnit ) {
	'use strict';

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
			group: TEST_SITE_GROUPS[ 0 ],
			languageCode: 'nn',
			name: null,
			pageUrl: '//nn.wikipedia.org/wiki/$1',
			shortName: 'norsk nynorsk'
		},
		'foo-site': {
			apiUrl: '//foo.site.bar/api.php',
			id: 'foo-site',
			group: TEST_SITE_GROUPS[ 1 ],
			languageCode: 'foo',
			name: null,
			pageUrl: '//foo.site.bar/pages/$1',
			shortName: 'foo site'
		},
		siteInExistingGroup: {
			apiUrl: '//someSite/api.php',
			globalSiteId: 'site-in-existing-group',
			group: TEST_SITE_GROUPS[ 1 ],
			id: 'siteInExistingGroup',
			languageCode: 'siteInExistingGroup',
			name: null,
			pageUrl: '//someSite/pages/$1',
			shortName: 'some site'
		}
	};

	QUnit.module( 'wikibase.sites', QUnit.newWbEnvironment( {
		config: {
			wbSiteDetails: TEST_SITE_DEFINITIONS
		}
	} ) );

	QUnit.test( 'basic', function ( assert ) {
		assert.ok(
			wb.sites instanceof Object,
			'initiated wikibase object'
		);
	} );

	/**
	 * Generic test for testing an object of sites as supposed to be returned by getSites() and
	 * others.
	 *
	 * TODO: Another reason why there should be a SiteList object, much easier to verify that the
	 *  returned value of different functions returning a list of sites is valid.
	 *
	 * @param {QUnit.assert} assert
	 * @param {string} fnName
	 * @param {Object} sites
	 */
	function siteSetTest( assert, fnName, sites ) {
		assert.ok(
			$.isPlainObject( sites ),
			fnName + '() returns a plain object'
		);

		var allSiteInstances = true;
		$.each( sites, function ( i, site ) {
			allSiteInstances = allSiteInstances && ( site instanceof Site );
		} );

		assert.ok(
			allSiteInstances,
			fnName + '() returned object fields only hold site objects'
		);
	}

	QUnit.test( 'wikibase.sites.getSites()', function ( assert ) {
		assert.expect( 3 );
		var sites = wb.sites.getSites();
		siteSetTest( assert, 'getSites', sites );

		assert.strictEqual(
			sites.length,
			TEST_SITE_DEFINITIONS.length,
			'getSites() returns expected number of sites'
		);
	} );

	QUnit.test( 'wikibase.sites.getSitesOfGroup()', function ( assert ) {
		assert.expect( 6 );
		$.each( TEST_SITE_GROUPS, function ( i, group ) {
			var sites = wb.sites.getSitesOfGroup( group );
			siteSetTest( assert, 'getSitesOfGroup', sites );

			var allFromRightGroup = true;
			$.each( sites, function ( i, site ) {
				allFromRightGroup = allFromRightGroup && ( site.getGroup() === group );
			} );

			assert.ok(
				allFromRightGroup,
				'getSitesOfGroup( "' + group + '" ) only returned sites of group "' + group + '"'
			);
		} );
	} );

	QUnit.test( 'wikibase.sites.getSite()', function ( assert ) {
		assert.ok(
			wb.sites.getSite( 'nnwiki' ) instanceof Site,
			'trying to get a known site by its ID returns a site object'
		);

		assert.strictEqual(
			wb.sites.getSite( 'unknown-site' ),
			null,
			'trying to get an unknown site returns null'
		);
	} );

	QUnit.test( 'wikibase.sites.hasSite()', function ( assert ) {
		assert.strictEqual(
			wb.sites.hasSite( 'nnwiki' ),
			true,
			'trying to check for known site returns true'
		);

		assert.strictEqual(
			wb.sites.hasSite( 'unknown-site' ),
			false,
			'trying to check for unknown site returns false'
		);
	} );

	QUnit.test( 'wikibase.sites.getSiteGroups()', function ( assert ) {
		assert.expect( 4 );
		var siteGroups = wb.sites.getSiteGroups();

		assert.strictEqual(
			Array.isArray( siteGroups ),
			true,
			'getSiteGroups() returns Array'
		);

		assert.strictEqual(
			siteGroups.length,
			TEST_SITE_GROUPS.length,
			'Number of expected groups is accurate'
		);

		$.each( TEST_SITE_GROUPS, function ( i, group ) {
			assert.ok(
				$.inArray( group, siteGroups ) !== -1,
				'site group "' + group + '" is part of returned site groups'
			);
		} );
	} );

}( wikibase, wikibase.Site, jQuery, QUnit ) );
