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
	function createSitelinkview( options ) {
		options = $.extend( {
			entityIdPlainFormatter: 'i am an EntityIdPlainFormatter',
			allowedSiteIds: [ 'aawiki', 'enwiki' ],
			getSiteLinkRemover: function () {
				return {
					destroy: function () {},
					disable: function () {},
					enable: function () {}
				};
			}
		}, options );

		return $( '<div>' )
			.addClass( 'test_sitelinkview' )
			.appendTo( document.body )
			.sitelinkview( options );
	}

	QUnit.module( 'jquery.wikibase.sitelinkview', QUnit.newMwEnvironment( {
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
						group: 'wikipedia'
					},
					enwiki: {
						apiUrl: 'http://en.wikipedia.org/w/api.php',
						name: 'English Wikipedia',
						pageUrl: 'http://en.wikipedia.org/wiki/$1',
						shortName: 'English',
						languageCode: 'en',
						id: 'enwiki',
						group: 'wikipedia'
					},
					dewiki: {
						apiUrl: 'http://de.wikipedia.org/w/api.php',
						name: 'Deutsche Wikipedia',
						pageUrl: 'http://de.wikipedia.org/wiki/$1',
						shortName: 'Deutsch',
						languageCode: 'de',
						id: 'dewiki',
						group: 'wikipedia'
					}
				}
			} );
		},
		afterEach: function () {
			$( '.test_sitelinkview' ).each( function () {
				var $sitelinkview = $( this ),
					sitelinkview = $sitelinkview.data( 'sitelinkview' );

				if ( sitelinkview ) {
					sitelinkview.destroy();
				}

				$sitelinkview.remove();
			} );
		}
	} ) );

	QUnit.test( 'Create and destroy', function ( assert ) {
		var $sitelinkview = createSitelinkview(),
			sitelinkview = $sitelinkview.data( 'sitelinkview' );

		assert.true(
			sitelinkview instanceof $.wikibase.sitelinkview,
			'Created widget.'
		);

		sitelinkview.destroy();

		assert.strictEqual(
			$sitelinkview.data( 'sitelinkview' ),
			undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'Create and destroy with initial value', function ( assert ) {
		var siteLink = new datamodel.SiteLink( 'enwiki', 'Main Page' ),
			$sitelinkview = createSitelinkview( {
				value: siteLink
			} ),
			sitelinkview = $sitelinkview.data( 'sitelinkview' );

		assert.true(
			sitelinkview instanceof $.wikibase.sitelinkview,
			'Created widget.'
		);

		sitelinkview.destroy();

		assert.strictEqual(
			$sitelinkview.data( 'sitelinkview' ),
			undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'startEditing() & stopEditing()', function ( assert ) {
		var $sitelinkview = createSitelinkview(),
			sitelinkview = $sitelinkview.data( 'sitelinkview' );

		$sitelinkview
		.on( 'sitelinkviewafterstartediting', function ( event ) {
			assert.true(
				true,
				'Started edit mode.'
			);
		} )
		.on( 'sitelinkviewafterstopediting', function ( event, dropValue ) {
			assert.true(
				true,
				'Stopped edit mode.'
			);
		} );

		sitelinkview.startEditing();
		sitelinkview.startEditing(); // should not trigger event
		sitelinkview.stopEditing( true );
		sitelinkview.stopEditing( true ); // should not trigger event
		sitelinkview.stopEditing(); // should not trigger event

		sitelinkview.startEditing();

		var siteselector = $sitelinkview.find( ':wikibase-siteselector' ).data( 'siteselector' ),
			$pagesuggester = $sitelinkview.find( ':wikibase-pagesuggester' );

		siteselector.setSelectedSite( wb.sites.getSite( 'aawiki' ) );

		sitelinkview.stopEditing(); // should not trigger event

		$pagesuggester.val( 'test' );

		sitelinkview.stopEditing();
	} );

	QUnit.test( 'startEditing(), stopEditing() with initial value', function ( assert ) {
		var siteLink = new datamodel.SiteLink( 'enwiki', 'Main Page' ),
			$sitelinkview = createSitelinkview( {
				value: siteLink
			} ),
			sitelinkview = $sitelinkview.data( 'sitelinkview' );

		$sitelinkview
		.on( 'sitelinkviewafterstartediting', function ( event ) {
			assert.true(
				true,
				'Started edit mode.'
			);
		} )
		.on( 'sitelinkviewafterstopediting', function ( event, dropValue ) {
			assert.true(
				true,
				'Stopped edit mode.'
			);
		} );

		sitelinkview.startEditing();

		assert.strictEqual(
			$sitelinkview.find( ':wikibase-siteselector' ).length,
			0,
			'Did not create a site selector widget.'
		);

		sitelinkview.stopEditing( true );

		sitelinkview.startEditing();

		var $pagesuggester = $sitelinkview.find( ':wikibase-pagesuggester' );

		sitelinkview.stopEditing(); // should not trigger event (value unchanged)

		$pagesuggester.val( 'test' );

		sitelinkview.stopEditing();
	} );

	QUnit.test( 'value()', function ( assert ) {
		var $sitelinkview = createSitelinkview(),
			sitelinkview = $sitelinkview.data( 'sitelinkview' );

		assert.strictEqual(
			sitelinkview.value(),
			null,
			'Returning null when no value is set.'
		);

		var siteLink = new datamodel.SiteLink( 'enwiki', 'Main Page' );

		$sitelinkview = createSitelinkview( {
			value: siteLink
		} );
		sitelinkview = $sitelinkview.data( 'sitelinkview' );

		assert.strictEqual(
			sitelinkview.value(),
			siteLink,
			'Returning SiteLink object when a valid value is set.'
		);
	} );

	QUnit.test( 'isEmpty()', function ( assert ) {
		var siteLink = new datamodel.SiteLink( 'enwiki', 'Main Page' ),
			$sitelinkview = createSitelinkview(),
			sitelinkview = $sitelinkview.data( 'sitelinkview' );

		assert.strictEqual(
			sitelinkview.isEmpty(),
			true,
			'isEmpty() returns TRUE when no site link is set and the widget is not in edit mode.'
		);

		sitelinkview.startEditing();

		assert.strictEqual(
			sitelinkview.isEmpty(),
			true,
			'Verified isEmpty() returning TRUE when no site link is set, the widget is in edit '
			+ 'and input elements are empty.'
		);

		$sitelinkview.find( ':wikibase-siteselector' ).val( 'site' );

		assert.strictEqual(
			sitelinkview.isEmpty(),
			false,
			'Widget is not empty when the site selector is filled with input.'
		);

		$sitelinkview.find( ':wikibase-siteselector' ).val( '' );
		$sitelinkview.find( ':wikibase-pagesuggester' ).val( 'page' );

		assert.strictEqual(
			sitelinkview.isEmpty(),
			false,
			'Widget is not empty when the page suggester is filled with input.'
		);

		$sitelinkview = createSitelinkview( {
			value: siteLink
		} );
		sitelinkview = $sitelinkview.data( 'sitelinkview' );

		assert.strictEqual(
			sitelinkview.isEmpty(),
			false,
			'isEmpty() returns FALSE when a site link is set initially.'
		);

		sitelinkview.startEditing();
		$sitelinkview.find( ':wikibase-pagesuggester' ).val( '' );

		assert.strictEqual(
			sitelinkview.isEmpty(),
			false,
			'isEmpty() returns FALSE when a site link is set initially although the page suggester '
			+ ' input is cleared in edit mode.'
		);
	} );

	QUnit.test( 'setError()', function ( assert ) {
		var $sitelinkview = createSitelinkview(),
			sitelinkview = $sitelinkview.data( 'sitelinkview' );

		$sitelinkview
		.addClass( 'wb-error' )
		.on( 'sitelinkviewtoggleerror', function ( event, error ) {
			assert.true(
				true,
				'Triggered toggleerror event.'
			);
		} );

		sitelinkview.setError();
	} );

}( wikibase ) );
