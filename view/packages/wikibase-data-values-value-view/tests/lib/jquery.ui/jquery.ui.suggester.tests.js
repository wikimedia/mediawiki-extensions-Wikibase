/**
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, QUnit ) {
	'use strict';

	var defaultSource = [
		'a',
		'ab',
		'abc',
		'd',
		'EFG'
	];

	/**
	 * Factory creating a jQuery.ui.suggester widget suitable for testing.
	 *
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	var newTestSuggester = function( options ) {
		options = $.extend( {
			source: defaultSource
		}, options || {} );

		return $( '<input/>' )
			.addClass( 'test_suggester' )
			.appendTo( 'body' )
			.suggester( options );
	};

	/**
	 * @return {ui.ooMenu}
	 */
	var createCustomMenu = function() {
		var $menu = $( '<ul/>' ).ooMenu( {
			customItems: [
				new $.ui.ooMenu.CustomItem( 'custom item' )
			]
		} );
		return $menu.data( 'ooMenu' );
	};

	QUnit.module( 'jquery.ui.suggester', {
		afterEach: function() {
			var $suggester = $( '.test_suggester' ),
				suggester = $suggester.data( 'suggester' );
			if ( suggester ) {
				suggester.destroy();
			}
			$suggester.remove();
		}
	} );

	QUnit.test( 'Create', function( assert ) {
		assert.expect( 1 );
		var $suggester = newTestSuggester();

		assert.ok(
			$suggester.data( 'suggester' ) instanceof $.ui.suggester,
			'Instantiated suggester.'
		);
	} );

	QUnit.test( '"menu" option', function( assert ) {
		var done = assert.async( 2 );
		var customMenu = createCustomMenu();

		var $suggester = newTestSuggester( {
			menu: customMenu
		} );

		var suggester = $suggester.data( 'suggester' );

		$suggester
		.one( 'suggesteropen', function() {
			assert.equal(
				suggester.options.menu,
				customMenu
			);

			done();
		} );

		$suggester.val( 'a' );
		suggester.search();

		customMenu = createCustomMenu();

		suggester.option( 'menu', customMenu );

		$suggester
		.one( 'suggesteropen', function() {
			assert.equal(
				suggester.options.menu,
				customMenu
			);

			done();
		} );

		suggester.search();
	} );

	QUnit.test( 'search() gathering suggestions from an array', function( assert ) {
		assert.expect( 1 );
		var $suggester = newTestSuggester(),
			suggester = $suggester.data( 'suggester' );

		$suggester.val( 'a' );

		return suggester.search().then( function( suggestions ) {
			assert.equal(
				suggestions.length,
				3,
				'Gathered suggestions from array.'
			);
		} );
	} );

	QUnit.test( 'search() gathering suggestions from a function', function( assert ) {
		assert.expect( 1 );
		var $suggester = newTestSuggester( {
				source: function( term ) {
					var deferred = new $.Deferred();
					return deferred.resolve( [
						'suggestion 1',
						'suggestion 2'
					] ).promise();
				}
			} ),
			suggester = $suggester.data( 'suggester' );

		$suggester.val( 'a' );

		return suggester.search().then( function( suggestions ) {
			assert.equal(
				suggestions.length,
				2,
				'Gathered suggestions from function.'
			);
		} );
	} );

	QUnit.test( 'isSearching() - triggering search() programmatically', function( assert ) {
		assert.expect( 3 );
		var $suggester = newTestSuggester( {
				source: function( term ) {
					var deferred = new $.Deferred();

					setTimeout( function() {
						deferred.resolve( [
							'suggestion 1',
							'suggestion 2'
						] );
					}, 100 );

					return deferred.promise();
				}
			} ),
			suggester = $suggester.data( 'suggester' );

		assert.ok(
			!suggester.isSearching(),
			'Returning FALSE when not having triggered a search.'
		);

		$suggester.val( 'a' );

		var promise = suggester.search();

		assert.ok(
			suggester.isSearching(),
			'Returning TRUE while search is in progress.'
		);

		return promise.then( function( suggestions ) {
			assert.ok(
				!suggester.isSearching(),
				'Returning FALSE after search has finished.'
			);
		} );
	} );

	QUnit.test( 'isSearching() - triggering with "key" event', function( assert ) {
		assert.expect( 3 );
		var $suggester = newTestSuggester( {
				source: function( term ) {
					var deferred = new $.Deferred();

					setTimeout( function() {
						deferred.resolve( [
							'suggestion 1',
							'suggestion 2'
						] );
					}, 10 );

					return deferred.promise();
				}
			} ),
			suggester = $suggester.data( 'suggester' ),
			done = assert.async();

		assert.ok(
			!suggester.isSearching(),
			'Returning FALSE when not having triggered a search.'
		);

		$suggester.val( 'a' );

		// First "change" event is triggered directly on "key" event.
		$suggester.one( 'suggesterchange', function() {
			// Second "change" event is triggered after gathering the suggestions.
			$suggester.one( 'suggesterchange', function() {
				assert.ok(
					!suggester.isSearching(),
					'Returning FALSE after search has finished.'
				);

				done();
			} );
		} );

		$suggester.trigger( 'keydown' );

		assert.ok(
			suggester.isSearching(),
			'Returning TRUE while search is in progress.'
		);
	} );

	QUnit.test( 'Error', function( assert ) {
		assert.expect( 2 );
		var $suggester = newTestSuggester( {
				source: function( term ) {
					var deferred = new $.Deferred();
					return deferred.reject( 'error string' ).promise();
				}
			} ),
			suggester = $suggester.data( 'suggester' ),
			done = assert.async();

		$suggester.on( 'suggestererror', function( event, errorString ) {
			assert.equal(
				errorString,
				'error string',
				'Validated expected error string.'
			);
		} );

		$suggester.val( 'a' );

		suggester.search()
		.done( function( suggestions ) {
			assert.ok(
				false,
				'Searching was successful although it should have failed.'
			);
		} )
		.fail( function() {
			assert.ok(
				true,
				'Searching failed as expected.'
			);
		} )
		.always( done );
	} );

}( jQuery, QUnit ) );
