/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, wb, QUnit ) {
	'use strict';

	/**
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	function createSitelinkview( options ) {
		options = $.extend( {
			entityStore: new wb.store.EntityStore(),
			allowedSiteIds: ['aawiki', 'enwiki']
		}, options );

		return $( '<div/>' )
			.addClass( 'test_sitelinkview' )
			.appendTo( $( 'body' ) )
			.sitelinkview( options );
	}

	QUnit.module( 'jquery.wikibase.sitelinkview', QUnit.newWbEnvironment( {
		config: {
			'wbSiteDetails': {
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
		},
		teardown: function() {
			$( '.test_sitelinkview' ).each( function() {
				var $sitelinkview = $( this ),
					sitelinkview = $sitelinkview.data( 'sitelinkview' );

				if( sitelinkview ) {
					sitelinkview.destroy();
				}

				$sitelinkview.remove();
			} );
		}
	} ) );

	QUnit.test( 'Create and destroy', function( assert ) {
		var $sitelinkview = createSitelinkview(),
			sitelinkview = $sitelinkview.data( 'sitelinkview' );

		assert.ok(
			sitelinkview instanceof $.wikibase.sitelinkview,
			'Created widget.'
		);

		sitelinkview.destroy();

		assert.ok(
			$sitelinkview.data( 'sitelinkview' ) === undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'Create and destroy with initial value', function( assert ) {
		var siteLink = new wikibase.datamodel.SiteLink( 'enwiki', 'Main Page' ),
			$sitelinkview = createSitelinkview( {
				value: siteLink
			} ),
			sitelinkview = $sitelinkview.data( 'sitelinkview' );

		assert.ok(
			sitelinkview instanceof $.wikibase.sitelinkview,
			'Created widget.'
		);

		sitelinkview.destroy();

		assert.ok(
			$sitelinkview.data( 'sitelinkview' ) === undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'startEditing() & stopEditing()', 4, function( assert ) {
		var $sitelinkview = createSitelinkview(),
			sitelinkview = $sitelinkview.data( 'sitelinkview' );

		$sitelinkview
		.on( 'sitelinkviewafterstartediting', function( event ) {
			assert.ok(
				true,
				'Started edit mode.'
			);
		} )
		.on( 'sitelinkviewstopediting', function( event, dropValue, callback ) {
			callback();
		} )
		.on( 'sitelinkviewafterstopediting', function( event, dropValue ) {
			assert.ok(
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

	QUnit.test( 'startEditing(), stopEditing() with initial value', 5, function( assert ) {
		var siteLink = new wikibase.datamodel.SiteLink( 'enwiki', 'Main Page' ),
			$sitelinkview = createSitelinkview( {
				value: siteLink
			} ),
			sitelinkview = $sitelinkview.data( 'sitelinkview' );

		$sitelinkview
		.on( 'sitelinkviewafterstartediting', function( event ) {
			assert.ok(
				true,
				'Started edit mode.'
			);
		} )
		.on( 'sitelinkviewstopediting', function( event, dropValue, callback ) {
			callback();
		} )
		.on( 'sitelinkviewafterstopediting', function( event, dropValue ) {
			assert.ok(
				true,
				'Stopped edit mode.'
			);
		} );

		sitelinkview.startEditing();

		assert.ok(
			$sitelinkview.find( ':wikibase-siteselector' ).length === 0,
			'Did not create a site selector widget.'
		);

		sitelinkview.stopEditing( true );

		sitelinkview.startEditing();

		var $pagesuggester = $sitelinkview.find( ':wikibase-pagesuggester' );

		sitelinkview.stopEditing(); // should not trigger event (value unchanged)

		$pagesuggester.val( 'test' );

		sitelinkview.stopEditing();
	} );

	QUnit.test( 'value()', function( assert ) {
		var $sitelinkview = createSitelinkview(),
			sitelinkview = $sitelinkview.data( 'sitelinkview' );

		assert.strictEqual(
			sitelinkview.value(),
			null,
			'Returning null when no value is set.'
		);

		var siteLink = new wikibase.datamodel.SiteLink( 'enwiki', 'Main Page' );

		$sitelinkview = createSitelinkview( {
			value: siteLink
		} );
		sitelinkview = $sitelinkview.data( 'sitelinkview' );

		assert.equal(
			sitelinkview.value(),
			siteLink,
			'Returning SiteLink object when a valid value is set.'
		);
	} );

	QUnit.test( 'isValid()', function( assert ) {
		var $sitelinkview = createSitelinkview(),
			sitelinkview = $sitelinkview.data( 'sitelinkview' );

		assert.ok(
			!sitelinkview.isValid(),
			'Returning false after initializing with no value.'
		);

		var siteLink = new wikibase.datamodel.SiteLink( 'enwiki', 'Main Page' );

		$sitelinkview = createSitelinkview( {
			value: siteLink
		} );
		sitelinkview = $sitelinkview.data( 'sitelinkview' );

		assert.ok(
			sitelinkview.isValid(),
			'Returning true after initializing with a proper value.'
		);

		sitelinkview.startEditing();
		$sitelinkview.find( ':wikibase-pagesuggester' ).val( '' );

		assert.ok(
			!sitelinkview.isValid(),
			'Returning false after erasing the page name.'
		);

		$sitelinkview.find( ':wikibase-pagesuggester' ).val( 'test' );

		assert.ok(
			sitelinkview.isValid(),
			'Returning true after specifying another page name.'
		);
	} );

	QUnit.test( 'isInitialValue()', function( assert ) {
		var siteLink = new wikibase.datamodel.SiteLink( 'enwiki', 'Main Page' ),
			$sitelinkview = createSitelinkview( {
				value: siteLink
			} ),
			sitelinkview = $sitelinkview.data( 'sitelinkview' );

		assert.ok(
			sitelinkview.isInitialValue(),
			'Returning true after initializing with a proper value.'
		);

		sitelinkview.startEditing();

		$sitelinkview.find( ':wikibase-pagesuggester' ).val( 'test' );

		assert.ok(
			!sitelinkview.isInitialValue(),
			'Returning false after changing the value.'
		);

		$sitelinkview.find( ':wikibase-pagesuggester' ).val( 'Main Page' );

		assert.ok(
			sitelinkview.isInitialValue(),
			'Returning true after resetting the value.'
		);
	} );

	QUnit.test( 'setError()', 1, function( assert ) {
		var $sitelinkview = createSitelinkview(),
			sitelinkview = $sitelinkview.data( 'sitelinkview' );

		$sitelinkview
		.addClass( 'wb-error' )
		.on( 'sitelinkviewtoggleerror', function( event, error ) {
			assert.ok(
				true,
				'Triggered toggleerror event.'
			);
		} );

		sitelinkview.setError();
	} );


}( jQuery, wikibase, QUnit ) );
