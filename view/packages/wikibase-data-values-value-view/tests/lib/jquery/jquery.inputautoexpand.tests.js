/**
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function () {
	'use strict';

	/**
	 * Factory for creating a new input element suited for testing.
	 *
	 * @return {jQuery} input element
	 */
	var newTestInputAutoExpand = function() {
		var $input = $( '<input/>', {
			class: 'test_inputautoexpand',
			width: '20px',
			type: 'text'
		} )
		// Append to body to be able to detect the element width:
		.appendTo( 'body' );

		/**
		 * Changes the text of the input field and triggers expansion/contraction.
		 *
		 * @param {string} text
		 */
		$input.testInsert = function( text ) {
			this.val( text );
			this.data( 'inputautoexpand' ).expand();
		};

		return $input;
	};

	/**
	 * Factory for creating a new textarea element suited for testing.
	 *
	 * @return {jQuery} textarea element
	 */
	var newTestTextareaAutoExpand = function() {
		var $textarea = $( '<textarea/>', {
			class: 'test_inputautoexpand',
			width: '20px'
		} )
		// Append to body to be able to detect the element width:
		.appendTo( 'body' );

		/**
		 * Changes the text of the textarea and triggers expansion/contraction.
		 *
		 * @param {string} text
		 */
		$textarea.testInsert = function( text ) {
			this.text( text );
			this.data( 'inputautoexpand' ).expand();
		};

		return $textarea;
	};

	QUnit.module( 'jquery.inputautoexpand', {
		afterEach: function() {
			$( '.test_inputautoexpand' ).remove();
		}
	} );

	QUnit.test( 'Initialize plugin', function( assert ) {
		var $input = newTestInputAutoExpand(),
			$textarea = newTestTextareaAutoExpand(),
			$div = $( '<div/>' ).addClass( 'test_inputautoexpand' ).appendTo( 'body' );

		assert.strictEqual(
			$input.inputautoexpand(),
			$input,
			'Initialized plugin on input box.'
		);

		assert.strictEqual(
			$textarea.inputautoexpand(),
			$textarea,
			'Initialized plugin.'
		);

		$div.inputautoexpand();

		assert.strictEqual(
			undefined,
			$div.data( 'inputautoexpand' ),
			'Not initializing plugin on div.'
		);
	} );

	QUnit.test( 'Applying plugin to input boxes', function( assert ) {
		var $input = newTestInputAutoExpand(),
			initialWidth = Math.ceil( $input.width() ),
			previousWidth,
			currentWidth;

		$input.inputautoexpand();

		$input.testInsert( 'OOOOOOOOOO' );
		currentWidth = Math.ceil( $input.width() );

		assert.ok(
			currentWidth > initialWidth,
			'Input field grows when inserting a string. '
				+ '(initial: ' + initialWidth + ', current: ' + currentWidth + ')'
		);

		$input.attr( 'placeholder', 'OOOOOOOOOO OOOOOOOOOO' );

		previousWidth = currentWidth;
		$input.testInsert( 'OOO' );
		currentWidth = Math.ceil( $input.width() );

		assert.ok(
			currentWidth > previousWidth,
			'Input field has grown after setting a placeholder longer than the current input. '
				+ '(previous: ' + previousWidth + ', current: ' + currentWidth + ')'
		);

		previousWidth = currentWidth;
		$input.testInsert( 'O' );
		currentWidth = Math.ceil( $input.width() );

		assert.strictEqual(
			previousWidth,
			currentWidth,
			'Width does not change when clearing the input while a placeholder longer than the '
				+ 'erased input is set. '
				+ '(previous: ' + previousWidth + ', current: ' + currentWidth + ')'
		);

		$input.removeAttr( 'placeholder' );

		previousWidth = currentWidth;
		$input.testInsert( '' );
		currentWidth = Math.ceil( $input.width() );

		assert.ok(
			currentWidth < previousWidth,
			'Input element contracts after removing the placeholder. '
				+ '(previous: ' + previousWidth + ', current: ' + currentWidth + ', initial: '
				+ initialWidth + ')'
		);
	} );

	QUnit.test( 'Applying horizontally growing plugin to textareas', function( assert ) {
		var $textarea = newTestTextareaAutoExpand().inputautoexpand(),
			initialWidth = Math.ceil( $textarea.width() ),
			previousWidth,
			currentWidth;

		$textarea.testInsert( 'OOOOOOOOOO' );
		currentWidth = Math.ceil( $textarea.width() );

		assert.ok(
			currentWidth > initialWidth,
			'Textarea grows when inserting a string. '
				+ '(initial: ' + initialWidth + ', current: ' + currentWidth + ')'
		);

		previousWidth = currentWidth;
		$textarea.testInsert( 'OOO' );
		currentWidth = Math.ceil( $textarea.width() );

		assert.ok(
			currentWidth < previousWidth,
			'Width shrinks when removing characters. '
				+ '(previous: ' + previousWidth + ', current: ' + currentWidth + ')'
		);

		previousWidth = currentWidth;
		$textarea.testInsert( '' );
		currentWidth = Math.ceil( $textarea.width() );

		assert.strictEqual(
			initialWidth,
			currentWidth,
			'Textarea contracts to initial width after erasing its content. '
				+ '(previous: ' + previousWidth + ', current: ' + currentWidth + ', initial: '
				+ initialWidth + ')'
		);
	} );

	QUnit.test( 'Applying vertically growing plugin to textareas', function( assert ) {
		var $textarea = newTestTextareaAutoExpand();

		// Init plugin before measuring the initial height since the plugin will shrink the textarea
		// to one line first:
		$textarea.inputautoexpand( { expandWidth: false, expandHeight: true } );
		$textarea.css( 'word-wrap', 'normal' );

		var initialHeight = Math.ceil( $textarea.height() ),
			previousHeight,
			currentHeight;

		$textarea.testInsert( 'a\na' );
		currentHeight = Math.ceil( $textarea.height() );

		assert.ok(
			currentHeight > initialHeight,
			'Textarea grows when inserting a new line. '
				+ '(initial: ' + initialHeight + ', current: ' + currentHeight + ')'
		);

		previousHeight = currentHeight;
		$textarea.testInsert( 'a\naa' );
		currentHeight = Math.ceil( $textarea.height() );

		assert.strictEqual(
			previousHeight,
			currentHeight,
			'Textarea does not grow when adding characters to an existing line. '
				+ '(previous: ' + previousHeight + ', current: ' + currentHeight + ')'
		);

		previousHeight = currentHeight;
		$textarea.testInsert( '' );
		currentHeight = Math.ceil( $textarea.height() );

		assert.strictEqual(
			initialHeight,
			currentHeight,
			'Textarea contracts to initial height after erasing its content. '
				+ '(previous: ' + previousHeight + ', current: ' + currentHeight + ', '
				+ 'initial: ' + initialHeight + ')'
		);
	} );

	QUnit.test( 'Applying horizontally and vertically growing plugin to textareas', function( assert ) {
		var $textarea = newTestTextareaAutoExpand(),
			MAXIMUM_WIDTH = 150;

		$textarea.inputautoexpand( {
			expandWidth: true,
			expandHeight: true,
			maxWidth: MAXIMUM_WIDTH
		} );

		var initialHeight = Math.ceil( $textarea.height() ),
			initialWidth = Math.ceil( $textarea.width() ),
			previousHeight,
			previousWidth,
			currentHeight,
			currentWidth;

		// The following string should be >20px and <MAXIMUM_WIDTHpx:
		$textarea.testInsert( 'OOOOOOOOOO' );
		currentHeight = Math.ceil( $textarea.height() );
		currentWidth = Math.ceil( $textarea.width() );

		assert.strictEqual(
			initialHeight,
			currentHeight,
			'Textarea does not grow vertically when inserting a string shorter than the maximum '
				+ 'width. '
				+ '(initial: ' + initialHeight + ', current: ' + currentHeight + ')'
		);

		assert.ok(
			currentWidth > initialWidth,
			'Textarea grows horizontally when inserting a string shorter than the maximum width. '
				+ '(initial: ' + initialWidth + ', current: ' + currentWidth + ')'
		);

		previousHeight = currentHeight;
		previousWidth = currentWidth;
		// The following string should be >MAXIMUM_WIDTHpx:
		$textarea.testInsert( 'OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO' );
		currentHeight = Math.ceil( $textarea.height() );
		currentWidth = Math.ceil( $textarea.outerWidth() );

		assert.ok(
			currentHeight > previousHeight,
			'Textarea grows vertically when inserting a string longer than the maximum width. '
				+ '(previous: ' + previousHeight + ', current: ' + currentHeight + ')'
		);

		assert.ok(
			currentWidth === MAXIMUM_WIDTH
			// Consider rounding:
			|| currentWidth + 1 === MAXIMUM_WIDTH || currentWidth - 1 === MAXIMUM_WIDTH,
			'Textarea grows to maximum width when inserting a string longer than the maximum '
				+ 'width. '
				+ '(previous: ' + previousWidth + ', current: ' + currentWidth + ')'
		);

		previousHeight = currentHeight;
		previousWidth = currentWidth;
		$textarea.testInsert( '' );
		currentHeight = Math.ceil( $textarea.height() );
		currentWidth = Math.ceil( $textarea.width() );

		assert.strictEqual(
			initialHeight,
			currentHeight,
			'Textarea contracts to initial height after erasing its content. '
				+ '(previous: ' + previousHeight + ', current: ' + currentHeight + ', '
				+ 'initial: ' + initialHeight + ')'
		);

		assert.strictEqual(
			initialWidth,
			currentWidth,
			'Textarea contracts to initial width after erasing its content. '
				+ '(previous: ' + previousWidth + ', current: ' + currentWidth + ', initial: '
				+ initialWidth + ')'
		);
	} );

}() );
