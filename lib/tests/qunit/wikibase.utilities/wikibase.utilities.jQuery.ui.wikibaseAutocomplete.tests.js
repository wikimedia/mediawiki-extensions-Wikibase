/**
 * QUnit tests for wikibaseAutocomplete jQuery widget
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */

( function( mw, wb, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'wikibase.utilities.jQuery.ui.wikibaseAutocomplete', QUnit.newWbEnvironment( {
		setup: function() {
			this.subject = $( '<input/>' ).wikibaseAutocomplete( {
				source: [
					'a',
					'ab',
					'abc',
					'd'
				],
				maxItems: 4 // will be used in test 'automatic height adjustment'
			} );

			this.reopenMenu = function() {
				this.autocomplete.close();
				this.autocomplete.search( 'a' );
			};

			this.autocomplete = this.subject.data( 'wikibaseAutocomplete' );
		},
		teardown: function() {
			this.subject.remove();
			this.reopenMenu = null;
		}
	} ) );


	QUnit.test( 'basic tests', function( assert ) {

		this.subject.val( 'a' );
		this.autocomplete.search( 'a' ); // trigger opening menu

		assert.equal(
			this.autocomplete.menu.element.find( 'a' ).children( 'b' ).length,
			3,
			'Highlighted matching characers within the suggestions.'
		);

		var fullHeight = this.autocomplete.menu.element.height(); // height of all found items
		this.autocomplete.options.maxItems = 2;
		this.autocomplete.search( 'a' );

		assert.ok(
			fullHeight > this.autocomplete.menu.element.height(),
			'Suggestion menu gets resized.'
		);

		QUnit.test( 'calculating scrollbar width', function( assert ) {
			assert.ok(
				this.autocomplete._getScrollbarWidth() > 0,
				'Detected scrollbar width.'
			);
		} );

		// Firefox will throw an error when the input element is not part of the DOM while trying to
		// set the selection range which is part of the following assertion
		$( 'body' ).append( this.subject );
		assert.equal(
			this.autocomplete.autocompleteString( this.subject.val(), 'ab' ),
			1,
			'Auto-completed text.'
		);

	} );

	QUnit.test( 'automatic height adjustment', function( assert ) {

		var additionalResults = [
			'a1',
			'a2',
			'a3'
		];

		this.autocomplete.search( 'a' );

		var initHeight = this.autocomplete.menu.element.height();
		this.autocomplete.options.source.push( additionalResults[0] );
		this.reopenMenu();

		// testing (MAX_ITEMS - 1)++
		assert.ok(
			this.autocomplete.menu.element.height() > initHeight,
			'height changed after adding another item to result set'
		);

		// adding one more item (MAX_ITEMS + 1) first, since there might be side effects adding the scrollbar
		this.autocomplete.options.source.push( additionalResults[1] );
		this.reopenMenu();
		initHeight = this.autocomplete.menu.element.height();

		this.autocomplete.options.source.push( additionalResults[2] );
		this.reopenMenu();

		// testing (MAX_ITEMS + 1)++
		assert.equal(
			this.autocomplete.menu.element.height(),
			initHeight,
			'height unchanged after adding more than maximum items'
		);

	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
