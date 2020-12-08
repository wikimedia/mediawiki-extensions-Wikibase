jQuery.fn.autocompletestring = ( function () {
	'use strict';

	/**
	 * Applied to an input or textarea element, `jQuery.fn.autocompletestring` is fed with a
	 * "complete" string and an "incomplete" - latter is supposed to consist out of the first
	 * letter(s) of the "complete" string. The form element is filled with the "complete" string
	 * while a text selection is applied to the characters missing in the "incomplete" string.
	 *
	 * @member jQuery.fn
	 * @method autocompletestring
	 * @license GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @param {string} incomplete
	 * @param {string} complete
	 * @return {jQuery}
	 */
	var autocompletestring = function( incomplete, complete ) {
		if ( !incomplete
			|| !complete
			|| complete.toLowerCase().indexOf( incomplete.toLowerCase() ) !== 0
		) {
			return this;
		}

		return this.each( function() {
			var $this = $( this );

			// Only auto-complete when incomplete string actually is a part of the complete string:
			if ( incomplete === complete.substr( 0, incomplete.length ) ) {
				$this.val( incomplete + complete.substr( incomplete.length ) );
			}

			$.fn.autocompletestring.selectText( this, incomplete.length, complete.length );
		} );
	};

	/**
	 * Creates a text selection.
	 *
	 * @ignore
	 *
	 * @param {Object} node
	 * @param {number} start
	 * @param {number} end
	 * @return {number} Text selection length.
	 */
	autocompletestring.selectText = function( node, start, end ) {
		if ( end > node.value.length ) {
			end = node.value.length;
		}

		if ( start > end ) {
			return 0;
		}

		if ( node.createTextRange ) { // Opera < 10.5 and IE
			var selRange = node.createTextRange();
			selRange.collapse( true );
			selRange.moveStart( 'character', start );
			selRange.moveEnd( 'character', end );
			selRange.select();
		} else if ( node.setSelectionRange ) { // major modern browsers
			// Make a 'backward' selection so pressing arrow left won't put the cursor near the
			// selections end but rather at the typing position:
			node.setSelectionRange( start, end, 'backward' );
		} else if ( node.selectionStart ) {
			node.selectionStart = start;
			node.selectionEnd = end;
		}

		return ( end - start );
	};

	return autocompletestring;

}() );
