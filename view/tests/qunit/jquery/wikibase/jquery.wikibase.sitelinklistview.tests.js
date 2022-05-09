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
	function createSitelinklistview( options ) {
		options = $.extend( {
			allowedSiteIds: [ 'aawiki', 'enwiki' ],
			getListItemAdapter: function () {
				return wb.tests.getMockListItemAdapter(
					'sitelinkview',
					function () {
						this.$siteId = $( '<div>' );
						this.focus = function () {};
						this.isEmpty = function () {
							return !this.options.value;
						};
						this.startEditing = function () {};
						this.value = function () {
							return this.options.value;
						};
					}
				);
			}
		}, options );

		var $sitelinklistview = $( '<table>' )
			.addClass( 'test_sitelinklistview' )
			.appendTo( document.body )
			.sitelinklistview( options );

		var sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

		sitelinklistview._saveSiteLink = function ( siteLink ) {
			if ( !( siteLink instanceof datamodel.SiteLink ) ) {
				throw new Error( 'SiteLink object expected' );
			} else {
				return ( new $.Deferred() ).resolve().promise();
			}
		};

		return $sitelinklistview;
	}

	QUnit.module( 'jquery.wikibase.sitelinklistview', QUnit.newMwEnvironment( {
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
			$( '.test_sitelinklistview' ).each( function () {
				var $sitelinklistview = $( this ),
					sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

				if ( sitelinklistview ) {
					sitelinklistview.destroy();
				}

				$sitelinklistview.remove();
			} );
		}
	} ) );

	QUnit.test( 'Create and destroy', function ( assert ) {
		var $sitelinklistview = createSitelinklistview(),
			sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

		assert.true(
			sitelinklistview instanceof $.wikibase.sitelinklistview,
			'Created widget.'
		);

		sitelinklistview.destroy();

		assert.strictEqual(
			$sitelinklistview.data( 'sitelinklistview' ),
			undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'Create and destroy with initial value', function ( assert ) {
		var siteLink = new datamodel.SiteLink( 'enwiki', 'Main Page' ),
			$sitelinklistview = createSitelinklistview( {
				value: [ siteLink ]
			} ),
			sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

		assert.true(
			sitelinklistview instanceof $.wikibase.sitelinklistview,
			'Created widget.'
		);

		sitelinklistview.destroy();

		assert.strictEqual(
			$sitelinklistview.data( 'sitelinklistview' ),
			undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'isFull()', function ( assert ) {
		var $sitelinklistview = createSitelinklistview(),
			sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

		assert.strictEqual(
			sitelinklistview.isFull(),
			false,
			'Returning false.'
		);

		$sitelinklistview = createSitelinklistview( {
			value: [
				new datamodel.SiteLink( 'aawiki', 'Main Page' ),
				new datamodel.SiteLink( 'enwiki', 'Main Page' )
			]
		} );
		sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

		assert.strictEqual(
			sitelinklistview.isFull(),
			true,
			'Returning true.'
		);
	} );

	QUnit.test( 'value() with invalid sitelinkview', function ( assert ) {
		var $sitelinklistview = createSitelinklistview( {
				value: []
			} ),
			sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

		sitelinklistview.enterNewItem();

		assert.strictEqual(
			sitelinklistview.value().length,
			0,
			'Verified value() returning valid values.'
		);
	} );

	QUnit.test( 'startEditing() & stopEditing()', function ( assert ) {
		var $sitelinklistview = createSitelinklistview( {
				value: [ new datamodel.SiteLink( 'enwiki', 'enwiki-page' ) ]
			} ),
			sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

		$sitelinklistview
		.on( 'sitelinklistviewafterstartediting', function ( event ) {
			assert.true(
				true,
				'Started edit mode.'
			);
		} )
		.on( 'sitelinklistviewafterstopediting', function ( event, dropValue ) {
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

			$sitelinklistview
			.one( 'sitelinklistviewafterstartediting.sitelinklistviewtest', function ( event ) {
				$sitelinklistview.off( '.sitelinklistviewtest' );
				deferred.resolve();
			} )
			.one(
				'sitelinklistviewafterstopediting.sitelinklistviewtest',
				function ( event, dropValue ) {
					$sitelinklistview.off( '.sitelinklistviewtest' );
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
			sitelinklistview.startEditing();
		} );

		addToQueue( function () {
			sitelinklistview.startEditing();
		}, false );

		addToQueue( function () {
			sitelinklistview.stopEditing( true );
		} );

		addToQueue( function () {
			sitelinklistview.stopEditing( true );
		}, false );

		addToQueue( function () {
			sitelinklistview.stopEditing();
		}, false );

		addToQueue( function () {
			sitelinklistview.startEditing();
		} );

		addToQueue( function () {
			// Mock adding a new item:
			var listview = sitelinklistview.$listview.data( 'listview' ),
				lia = listview.listItemAdapter(),
				$sitelinkview = listview.addItem( new datamodel.SiteLink( 'aawiki', 'aawiki-page' ) );
			lia.liInstance( $sitelinkview ).startEditing();
			sitelinklistview.stopEditing( true );
		} );

		$queue.dequeue( 'tests' );
	} );

	QUnit.test( 'setError()', function ( assert ) {
		var $sitelinklistview = createSitelinklistview(),
			sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

		$sitelinklistview
		.addClass( 'wb-error' )
		.on( 'sitelinklistviewtoggleerror', function ( event, error ) {
			assert.true(
				true,
				'Triggered toggleerror event.'
			);
		} );

		sitelinklistview.setError();
	} );

	QUnit.test( 'value()', function ( assert ) {
		var value = [ new datamodel.SiteLink( 'enwiki', 'Main Page' ) ],
			$sitelinklistview = createSitelinklistview( {
				value: value
			} ),
			sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

		assert.deepEqual(
			sitelinklistview.value(),
			value,
			'Retrieved initial value.'
		);

		value = [
			new datamodel.SiteLink( 'aawiki', 'a' ),
			new datamodel.SiteLink( 'aawiki', 'b' )
		];

		sitelinklistview.value( value );

		assert.deepEqual(
			sitelinklistview.value(),
			value,
			'Set and retrieved new value.'
		);
	} );

	QUnit.test( 'enterNewItem()', function ( assert ) {
		var $sitelinklistview = createSitelinklistview(),
			sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

		$sitelinklistview
		.on( 'sitelinklistviewafterstartediting', function () {
			assert.true(
				true,
				'Started sitelinklistview edit mode.'
			);
		} );

		sitelinklistview.enterNewItem();
	} );

	QUnit.test( 'remove empty sitelinkview when hitting backspace', function ( assert ) {
		var $sitelinklistview = createSitelinklistview(),
			sitelinklistview = $sitelinklistview.data( 'sitelinklistview' );

		// Have to create two because the last empty item is never removed
		sitelinklistview.enterNewItem();
		sitelinklistview.enterNewItem();

		var listview = sitelinklistview.$listview.data( 'listview' ),
			sitelinkview = listview.value()[ 0 ];

		sitelinkview.isEmpty = function () {
			return true;
		};

		assert.strictEqual( listview.items().length, 2 );
		var e = $.Event( 'keydown' );
		e.which = e.keyCode = $.ui.keyCode.BACKSPACE;
		sitelinkview.element.trigger( e );

		assert.strictEqual( listview.items().length, 1 );
	} );

}( wikibase ) );
