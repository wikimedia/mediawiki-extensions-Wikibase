/**
 * QUnit tests for suggester jQuery widget
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, QUnit, undefined ) {
	'use strict';

	/**
	 * Factory for creating a jquery.ui.suggester widget suitable for testing.
	 *
	 * @param {Object} [options]
	 *        default: { source: [ 'a', 'ab', 'abc', 'd' ], maxItems: 4 }
	 */
	var newTestSuggester = function( options ) {
		options = options || {
			maxItems: 4 // will be used in test 'automatic height adjustment'
		};
		if ( !options.source ) {
			options.source = [
				'a',
				'ab',
				'abc',
				'd',
				'EFG'
			];
		}

		// element needs to be in the DOM for setting text selection range
		var $input = $( '<input/>' ).addClass( 'test_suggester').appendTo( 'body' ).suggester( options );

		/**
		 * Shorthand function to reopen the menu by searching for a string that will produce at
		 * least one suggestion.
		 *
		 * @param {String} [search]
		 *        default: 'a'
		 */
		$input.data( 'suggester' ).reopenMenu = function( search ) {
			search = search || 'a';
			this.close();
			this.search( search );
		};

		return $input;
	};

	QUnit.module( 'jquery.ui.suggester', QUnit.newMwEnvironment( {
		teardown: function() {
			$( '.test_suggester' ).remove();
		}
	} ) );

	QUnit.test( 'basic tests', function( assert ) {
		var $input = newTestSuggester();
		var suggester = $input.data( 'suggester' );

		$input.on( 'suggesterresponse', function( event, items ) {
			assert.deepEqual(
				items,
				['ab', 'abc'],
				'Fired response event passing result set.'
			);
		} );
		$input.val( 'a' );
		suggester._success( ['a', ['ab', 'abc']] );
		$input.off( 'suggesterresponse' );

		assert.equal(
			$input.val(),
			'ab',
			'Replaced input element\'s value with first result (remaining part of the string is highlighted).'
		);

		suggester.close();

		$input.val( 'a' );
		suggester.search( 'a' ); // trigger opening menu

		assert.equal(
			suggester.menu.element.find( 'a' ).children( 'b' ).length,
			3,
			'Highlighted matching characters within the suggestions.'
		);

		var fullHeight = suggester.menu.element.height(); // height of all found items
		suggester.options.maxItems = 2;
		suggester.search( 'a' );

		assert.ok(
			fullHeight > suggester.menu.element.height(),
			'Suggestion menu gets resized.'
		);

		assert.ok(
			suggester._getScrollbarWidth() > 0,
			'Detected scrollbar width.'
		);

		// Firefox will throw an error when the input element is not part of the DOM while trying to
		// set the selection range which is part of the following assertion
		$( 'body' ).append( $input );
		assert.equal(
			suggester.autocompleteString( $input.val(), 'ab' ),
			1,
			'Auto-completed text.'
		);

		suggester.destroy();
		$input.remove();

	} );

	QUnit.test( 'Adapt letter case', function( assert ) {
		var $input = newTestSuggester();
		var suggester = $input.data( 'suggester' );

		assert.equal(
			suggester._adaptLetterCase( 'abc', 'AbC' ),
			'abc',
			"adaptLetterCase: Did not adapt any letter case."
		);

		$input.val( 'ef' );
		suggester.search( 'EF' ); // simulate case-insensitive search

		assert.equal(
			$input.val(),
			'ef',
			"Did not adjusted input value's letter case according to suggestion list's first result set."
		);

		suggester.destroy();
		$input.remove();

		$input = newTestSuggester( { adaptLetterCase: 'all' } );
		suggester = $input.data( 'suggester' );

		assert.equal(
			suggester._adaptLetterCase( 'abc', 'AbC' ),
			'AbC',
			"adjustLetterCase: Adapted the case of all letters."
		);

		$input.val( 'ef' );
		suggester.search( 'EF' );

		assert.equal(
			$input.val(),
			'EF',
			"Adjusted input value's letter case according to suggestion list's first result set."
		);

		suggester.destroy();
		$input.remove();

		$input = newTestSuggester( { adaptLetterCase: 'first' } );
		suggester = $input.data( 'suggester' );

		assert.equal(
			suggester._adaptLetterCase( 'abc', 'AbC' ),
			'Abc',
			"adaptLetterCase: Adapted the case of the first letter."
		);

		$input.val( 'ef' );
		suggester.search( 'EF' );

		assert.equal(
			$input.val(),
			'Ef',
			"Capitalized input value's letters according to suggestion list's first result set."
		);

		suggester.destroy();
		$input.remove();
	} );

	QUnit.test( 'automatic height adjustment', function( assert ) {
		var $input = newTestSuggester();
		var suggester = $input.data( 'suggester' );

		var additionalResults = [
			'a1',
			'a2',
			'a3'
		];

		suggester.search( 'a' );

		var initHeight = suggester.menu.element.height();
		suggester.options.source.push( additionalResults[0] );
		suggester.reopenMenu();

		// testing (MAX_ITEMS - 1)++
		assert.ok(
			suggester.menu.element.height() > initHeight,
			'height changed after adding another item to result set'
		);

		// adding one more item (MAX_ITEMS + 1) first, since there might be side effects adding the scrollbar
		suggester.options.source.push( additionalResults[1] );
		suggester.reopenMenu();
		initHeight = suggester.menu.element.height();

		suggester.options.source.push( additionalResults[2] );
		suggester.reopenMenu();

		// testing (MAX_ITEMS + 1)++
		assert.equal(
			suggester.menu.element.height(),
			initHeight,
			'height unchanged after adding more than maximum items'
		);

		suggester.destroy();
		$input.remove();

	} );

}( jQuery, QUnit ) );
