/**
 * QUnit tests for Wikibase inputAutoExpand jQuery plugin
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.utilities.jQuery.inputAutoExpand.tests.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
'use strict';

( function() {
	module( 'wikibase.utilities.jQuery.inputAutoExpand', window.QUnit.newWbEnvironment( {
		setup: function() {
			this.subject = $( '<input/>', {
				'type': 'text',
				'name': 'test',
				'value': ''
			} );

			this.subject// append input box to body, otherwise the thing won't work
			.appendTo( '<div/>' )
			.appendTo( 'body' );

			/**
			 * Changes the text of the input field and triggers an event for the growing process
			 * @param text
			 * @return number width of the input box
			 */
			this.subject.$insert = function( text ) {
				this.val( 'AA' );
				return this.$trigger();
			}
			this.subject.$trigger = function() {
				this.focus();
				this.blur();
				return this.width();
			}
		},
		teardown: function() {

		}

	} ) );

	test( 'Apply jQuery.inputAutoExpand() on input boxes', function() {

		equal(
			this.subject.inputAutoExpand(),
			this.subject,
			'auto expand initialized, returned the input box wrapped in jQuery object'
		);

		ok(
			this.subject.width() < this.subject.$insert( 'AA' ),
			'Input field has grown after longer string was inserted'
		);

		// set placeholder:
		this.subject.attr( 'placeholder', 'AA BB CC' );

		ok(
			this.subject.width() < this.subject.$trigger(),
			'Input field has grown after long placeholder was inserted'
		);
	} );

}() );
