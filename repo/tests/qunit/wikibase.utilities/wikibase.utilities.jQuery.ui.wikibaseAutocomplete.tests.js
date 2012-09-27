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
				]
			} );
			this.autocomplete = this.subject.data( 'wikibaseAutocomplete' );
		},
		teardown: function() {
			this.subject.remove();
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

		// Firefox will throw an error when the input element is not part of the DOM while trying to
		// set the selection range which is part of the following assertion
		$( 'body' ).append( this.subject );
		assert.equal(
			this.autocomplete.autocompleteString( this.subject.val(), 'ab' ),
			1,
			'Auto-completed text.'
		);

	} );


}( mediaWiki, wikibase, jQuery, QUnit ) );
