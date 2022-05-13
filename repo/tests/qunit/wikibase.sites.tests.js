/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */

( function ( wb, Site ) {
	'use strict';

	/**
	 * All site groups used in site definitions in TEST_SITE_DEFINITIONS.
	 *
	 * @type String[]
	 */
	var TEST_SITE_GROUPS = [ 'wikipedia', 'foo bar group' ];

	/**
	 * Site definition loaded to mw.config var "wbSiteDetails" before each test.
	 *
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

	QUnit.module( 'wikibase.sites', QUnit.newMwEnvironment( {
		beforeEach: function () {
			// empty cache of wikibases site details
			wb.sites._siteList = null;

			mw.config.set( {
				wbSiteDetails: TEST_SITE_DEFINITIONS
			} );
		}
	} ) );

	QUnit.test( 'basic', function ( assert ) {
		assert.true(
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
		assert.true(
			$.isPlainObject( sites ),
			fnName + '() returns a plain object'
		);

		var allSiteInstances = true;
		// eslint-disable-next-line no-jquery/no-each-util
		$.each( sites, function ( i, site ) {
			allSiteInstances = allSiteInstances && ( site instanceof Site );
		} );

		assert.true(
			allSiteInstances,
			fnName + '() returned object fields only hold site objects'
		);
	}

	QUnit.test( 'wikibase.sites.getSites()', function ( assert ) {
		var sites = wb.sites.getSites();
		siteSetTest( assert, 'getSites', sites );

		assert.strictEqual(
			sites.length,
			TEST_SITE_DEFINITIONS.length,
			'getSites() returns expected number of sites'
		);
	} );

	QUnit.test( 'wikibase.sites.getSitesOfGroup()', function ( assert ) {
		TEST_SITE_GROUPS.forEach( function ( group ) {
			var sites = wb.sites.getSitesOfGroup( group );
			siteSetTest( assert, 'getSitesOfGroup', sites );

			var allFromRightGroup = true;
			// eslint-disable-next-line no-jquery/no-each-util
			$.each( sites, function ( i, site ) {
				allFromRightGroup = allFromRightGroup && ( site.getGroup() === group );
			} );

			assert.true(
				allFromRightGroup,
				'getSitesOfGroup( "' + group + '" ) only returned sites of group "' + group + '"'
			);
		} );
	} );

	QUnit.test( 'wikibase.sites.getSite()', function ( assert ) {
		assert.true(
			wb.sites.getSite( 'nnwiki' ) instanceof Site,
			'trying to get a known site by its ID returns a site object'
		);

		assert.strictEqual(
			wb.sites.getSite( 'unknown-site' ),
			null,
			'trying to get an unknown site returns null'
		);
	} );

}( wikibase, wikibase.Site ) );
