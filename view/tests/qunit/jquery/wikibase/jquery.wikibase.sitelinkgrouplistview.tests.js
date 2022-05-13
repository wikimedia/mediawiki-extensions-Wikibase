/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	/**
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	function createSitelinkgrouplistview( options ) {
		options = $.extend( {
			listItemAdapter: wb.tests.getMockListItemAdapter(
				'sitelinkgroupview',
				function () {
				}
			)
		}, options );

		return $( '<div>' )
			.addClass( 'test_sitelinkgrouplistview' )
			.appendTo( document.body )
			.sitelinkgrouplistview( options );
	}

	QUnit.module( 'jquery.wikibase.sitelinkgrouplistview', QUnit.newMwEnvironment( {
		beforeEach: function () {
			// empty cache of wikibases site details
			wb.sites._siteList = null;

			mw.config.set( {
				wbSiteDetails: {
					aawiki: {
						apiUrl: 'http://aa.wikipedia.org/w/api.php',
						name: 'Qafár af',
						pageUrl: 'http://aa.wikipedia.org/wiki/$1',
						shortName: 'Qafár af',
						languageCode: 'aa',
						id: 'aawiki',
						group: 'group1'
					},
					enwiki: {
						apiUrl: 'http://en.wikipedia.org/w/api.php',
						name: 'English Wikipedia',
						pageUrl: 'http://en.wikipedia.org/wiki/$1',
						shortName: 'English',
						languageCode: 'en',
						id: 'enwiki',
						group: 'group1'
					},
					dewiki: {
						apiUrl: 'http://de.wikipedia.org/w/api.php',
						name: 'Deutsche Wikipedia',
						pageUrl: 'http://de.wikipedia.org/wiki/$1',
						shortName: 'Deutsch',
						languageCode: 'de',
						id: 'dewiki',
						group: 'group2'
					}
				}
			} );
		},
		afterEach: function () {
			$( '.test_sitelinkgrouplistview' ).each( function () {
				var $sitelinkgrouplistview = $( this ),
					sitelinkgrouplistview = $sitelinkgrouplistview.data( 'sitelinkgrouplistview' );

				if ( sitelinkgrouplistview ) {
					sitelinkgrouplistview.destroy();
				}

				$sitelinkgrouplistview.remove();
			} );
		}
	} ) );

	QUnit.test( 'Create & destroy', function ( assert ) {
		var value = new datamodel.SiteLinkSet( [
			new datamodel.SiteLink( 'aawiki', 'page1' ),
			new datamodel.SiteLink( 'dewiki', 'page1' ),
			new datamodel.SiteLink( 'enwiki', 'page2' )
		] );

		var $sitelinkgrouplistview = createSitelinkgrouplistview( {
				value: value
			} ),
			sitelinkgrouplistview = $sitelinkgrouplistview.data( 'sitelinkgrouplistview' );

		assert.notStrictEqual(
			sitelinkgrouplistview,
			undefined,
			'Created widget.'
		);

		sitelinkgrouplistview.destroy();

		assert.strictEqual(
			$sitelinkgrouplistview.data( 'sitelinkview' ),
			undefined,
			'Destroyed widget.'
		);

		assert.throws(
			function () {
				$sitelinkgrouplistview = createSitelinkgrouplistview();
			},
			'Widget does not accept an empty value.'
		);
	} );

}( wikibase ) );
