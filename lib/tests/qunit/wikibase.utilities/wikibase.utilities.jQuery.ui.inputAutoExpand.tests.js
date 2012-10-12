/**
 * QUnit tests for Wikibase 'inputAutoExpand' jQuery plugin
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
'use strict';

( function() {
	module( 'wikibase.utilities.jQuery.ui.inputAutoExpand', window.QUnit.newWbEnvironment( {
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
			 *
			 * @param string text
			 * @return number pixels of change in size
			 */
			this.subject.$insert = function( text ) {
				this.val( text );
				return this.$trigger();
			};
			/**
			 * Triggers the expand() of the AutoExpandInput
			 *
			 * @return {*}
			 */
			this.subject.$trigger = function() {
				var autoExpand = this.$getObj();
				return autoExpand.expand();
			};
			/**
			 * Returns the input boxes associated AutoExpandInput
			 *
			 * @return AutoExpandInput
			 */
			this.subject.$getObj = function() {
				return this.data( 'AutoExpandInput' );
			};
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
			this.subject.$insert( 'AA' ) > 0,
			'Input field has grown after longer string was inserted'
		);

		// set placeholder:
		this.subject.attr( 'placeholder', 'AA BB CC' );

		ok(
			this.subject.$trigger() > 0,
			'Input field has grown after long placeholder was inserted'
		);

		equal(
			this.subject.$insert( '' ),
			0,
			'Remove input fields text, size shouldn\'t change since we still have a placeholder'
		);

		// remove placeholder
		this.subject.attr( 'placeholder', null );
		this.subject.$trigger();

		equal(
			this.subject.$getObj().getWidth(),
			this.subject.$getObj().getComfortZone() + this.subject.innerWidth() - this.subject.width(),
			'Removed placeholder, width should be comfort zone width and padding of text box since there is no text set now'
		);
	} );

}() );
