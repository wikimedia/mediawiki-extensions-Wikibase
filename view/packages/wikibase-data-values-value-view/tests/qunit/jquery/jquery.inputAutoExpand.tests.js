/**
 * QUnit tests for inputAutoExpand jQuery plugin
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki at snater.com >
 */

( function( $, QUnit ) {
	'use strict';

	/**
	 * Factory for creating a new input element with auto-expand functionality suited for testing.
	 *
	 * @return {jQuery} input element
	 */
	var newTestInputAutoExpand = function() {
		var $input = $( '<input/>', {
			id: 'inputAutoExpandTest',
			type: 'text',
			name: 'test',
			value: ''
		} )
		.appendTo( 'body' ); // append input box to body, otherwise the thing won't work

		/**
		 * Changes the text of the input field and triggers an event for the growing process.
		 *
		 * @param {String} text
		 * @return {Number} Amount of the changed size in pixels
		 */
		$input.test_insert = function( text ) {
			this.val( text );
			return this.test_trigger();
		};

		/**
		 * Triggers the expand() of the AutoExpandInput.
		 *
		 * @return {*}
		 */
		$input.test_trigger = function() {
			var autoExpand = this.data( 'AutoExpandInput' );
			return autoExpand.expand();
		};

		return $input;
	};

	/**
	 * Factory for creating a new textarea element with auto-expand functionality suited for testing.
	 *
	 * @return {jQuery} textarea element
	 */
	var newTestTextareaAutoExpand = function() {
		var $textarea = $( '<textarea/>', {
			id: 'inputAutoExpandTest',
			width: '20px'
		} )
		.appendTo( 'body' ); // append input box to body, otherwise the thing won't work

		/**
		 * Changes the text of the textarea and triggers an event for the resize process.
		 *
		 * @param {String} text
		 * @return {Number} Amount of the changed size in pixels
		 */
		$textarea.test_insert = function( text ) {
			this.text( text );
			return this.test_trigger();
		};

		/**
		 * Triggers the expand() of the AutoExpandInput.
		 *
		 * @return {*}
		 */
		$textarea.test_trigger = function() {
			var autoExpand = this.data( 'AutoExpandInput' );
			return autoExpand.expand();
		};

		return $textarea;
	};

	QUnit.module( 'jquery.inputAutoExpand', QUnit.newMwEnvironment( {
		teardown: function() { $( '#inputAutoExpandTest' ).remove(); }
	} ) );

	QUnit.test( 'Apply jquery.inputAutoExpand() on input boxes', function( assert ) {
		var subject = newTestInputAutoExpand();

		assert.equal(
			subject.inputAutoExpand(),
			subject,
			'auto expand initialized, returned the input box wrapped in jQuery object'
		);

		assert.ok(
			subject.test_insert( 'AA' ) > 0,
			'Input field has grown after longer string was inserted'
		);

		// set placeholder:
		subject.attr( 'placeholder', 'AA BB CC' );

		assert.ok(
			subject.test_trigger() > 0,
			'Input field has grown after long placeholder was inserted'
		);

		assert.equal(
			subject.test_insert( '' ),
			0,
			'Remove input fields text, size shouldn\'t change since we still have a placeholder'
		);

		// remove placeholder
		subject.attr( 'placeholder', null );
		subject.test_trigger();

		assert.equal(
			subject.data( 'AutoExpandInput' ).getWidth(),
			subject.data( 'AutoExpandInput' ).getComfortZone() + subject.innerWidth() - subject.width(),
			'Removed placeholder, width should be comfort zone width and padding of text box since there is no text set now'
		);
	} );

	QUnit.test( 'Apply jquery.inputAutoExpand() on textareas', function( assert ) {
		var subject = newTestTextareaAutoExpand();

		assert.equal(
			subject.inputAutoExpand( { expandWidth: false, expandHeight: true } ),
			subject,
			'Initialized auto-expand, returned the textarea box wrapped in jQuery object.'
		);

		var initialHeight = subject.height();

		subject.test_insert( 'a\na' );

		assert.ok(
			subject.height() > initialHeight,
			'Input field has grown after a new line was inserted.'
		);

		var cachedHeight = subject.height();

		subject.test_insert( 'a\naa' );

		assert.ok(
			 subject.height() === cachedHeight,
			'Not growing when adding another character on the same line.'
		);

		subject.test_insert( '' );

		assert.ok(
			subject.height() === initialHeight,
			'Textarea contracted after removing text.'
		);

	} );

}( jQuery, QUnit ) );
