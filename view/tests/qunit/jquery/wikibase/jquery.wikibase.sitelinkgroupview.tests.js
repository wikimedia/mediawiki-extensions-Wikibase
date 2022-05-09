/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	/**
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	function createSitelinkgroupview( options ) {
		options = $.extend( {
			getSiteLinkListView: function ( value, $dom ) {
				var _value = value;
				var widget = {
					destroy: function () {},
					draw: function () {
						return $.Deferred().resolve().promise();
					},
					option: function () {},
					startEditing: function () {
						$dom.trigger( 'sitelinklistviewafterstartediting' );
						return $.Deferred().resolve().promise();
					},
					stopEditing: function () {
						return $.Deferred().resolve().promise();
					},
					value: function ( newValue ) {
						if ( arguments.length > 0 ) {
							_value = newValue;
						} else {
							return _value;
						}
					},
					widgetEventPrefix: ''
				};
				$dom.data( 'sitelinklistview', widget );
				return widget;
			}
		}, options );

		var $sitelinkgroupview = $( '<div>' )
			.addClass( 'test_sitelinkgroupview' )
			.appendTo( document.body )
			.sitelinkgroupview( options );

		return $sitelinkgroupview;
	}

	QUnit.module( 'jquery.wikibase.sitelinkgroupview', QUnit.newMwEnvironment( {
		beforeEach: function () {
			// empty cache of wikibases site details
			wikibase.sites._siteList = null;

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
			$( '.test_sitelinkgroupview' ).each( function () {
				var $sitelinkgroupview = $( this ),
					sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' );

				if ( sitelinkgroupview ) {
					sitelinkgroupview.destroy();
				}

				$sitelinkgroupview.remove();
			} );
		}
	} ) );

	QUnit.test( 'Create and destroy', function ( assert ) {
		var siteLink = new datamodel.SiteLink( 'enwiki', 'Main Page' ),
			$sitelinkgroupview = createSitelinkgroupview( {
				groupName: 'group1',
				value: new datamodel.SiteLinkSet( [ siteLink ] )
			} ),
			sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' );

		assert.notStrictEqual(
			sitelinkgroupview,
			undefined,
			'Created widget.'
		);

		sitelinkgroupview.destroy();

		assert.strictEqual(
			$sitelinkgroupview.data( 'sitelinkgroupview' ),
			undefined,
			'Destroyed widget.'
		);

		assert.throws(
			function () {
				$sitelinkgroupview = createSitelinkgroupview();
			},
			'Widget does not accept an empty value.'
		);
	} );

	QUnit.test( 'startEditing() & stopEditing()', function ( assert ) {
		var $sitelinkgroupview = createSitelinkgroupview( {
				groupName: 'group1',
				value: new datamodel.SiteLinkSet( [ new datamodel.SiteLink( 'enwiki', 'enwiki-page' ) ] )
			} ),
			sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' );

		$sitelinkgroupview
		.on( 'sitelinkgroupviewafterstartediting', function ( event ) {
			assert.true(
				true,
				'Started edit mode.'
			);
		} )
		.on( 'sitelinkgroupviewafterstopediting', function ( event, dropValue ) {
			assert.true(
				true,
				'Stopped edit mode.'
			);
		} );

		/**
		 * @param {Function} func
		 * @param {boolean} expectingEvent
		 * @return {Object} jQuery.Promise
		 */
		function testEditModeChange( func, expectingEvent ) {
			var deferred = $.Deferred();

			if ( !expectingEvent ) {
				func();
				return deferred.resolve().promise();
			}

			$sitelinkgroupview
			.one( 'sitelinkgroupviewafterstartediting.sitelinkgroupviewtest', function ( event ) {
				$sitelinkgroupview.off( '.sitelinkgroupviewtest' );
				deferred.resolve();
			} )
			.one(
				'sitelinkgroupviewafterstopediting.sitelinkgroupviewtest',
				function ( event, dropValue ) {
					$sitelinkgroupview.off( '.sitelinkgroupviewtest' );
					deferred.resolve();
				}
			);

			func();

			return deferred.promise();
		}

		var $queue = $( {} );

		/**
		 * @param {Function} func
		 * @param {boolean} [expectingEvent]
		 */
		function addToQueue( func, expectingEvent ) {
			if ( expectingEvent === undefined ) {
				expectingEvent = true;
			}
			$queue.queue( 'tests', function ( next ) {
				var done = assert.async();
				testEditModeChange( func, expectingEvent ).always( function () {
					next();
					done();
				} );
			} );
		}

		addToQueue( function () {
			sitelinkgroupview.startEditing();
		} );

		addToQueue( function () {
			sitelinkgroupview.startEditing();
		}, false );

		addToQueue( function () {
			sitelinkgroupview.stopEditing( true );
		} );

		addToQueue( function () {
			sitelinkgroupview.stopEditing( true );
		}, false );

		addToQueue( function () {
			sitelinkgroupview.stopEditing();
		}, false );

		addToQueue( function () {
			sitelinkgroupview.startEditing();
		} );

		$queue.dequeue( 'tests' );
	} );

	QUnit.test( 'setError()', function ( assert ) {
		var $sitelinkgroupview = createSitelinkgroupview( {
				groupName: 'group1',
				value: new datamodel.SiteLinkSet( [] )
			} ),
			sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' );

		$sitelinkgroupview
		.addClass( 'wb-error' )
		.on( 'sitelinkgroupviewtoggleerror', function ( event, error ) {
			assert.true(
				true,
				'Triggered toggleerror event.'
			);
		} );

		sitelinkgroupview.setError();
	} );

	QUnit.test( 'value()', function ( assert ) {
		var siteLink = new datamodel.SiteLink( 'enwiki', 'Main Page' ),
			siteLinks = new datamodel.SiteLinkSet( [ siteLink ] ),
			$sitelinkgroupview = createSitelinkgroupview( {
				groupName: 'group1',
				value: siteLinks
			} ),
			sitelinkgroupview = $sitelinkgroupview.data( 'sitelinkgroupview' );

		assert.deepEqual(
			sitelinkgroupview.value(),
			siteLinks,
			'Retrieved initial value.'
		);

		siteLinks = new datamodel.SiteLinkSet( [
			new datamodel.SiteLink( 'dewiki', '1234' ),
			new datamodel.SiteLink( 'enwiki', '5678' )
		] );

		sitelinkgroupview.value( siteLinks );

		assert.deepEqual(
			sitelinkgroupview.value(),
			siteLinks,
			'Set and retrieved new value.'
		);
	} );

}() );
