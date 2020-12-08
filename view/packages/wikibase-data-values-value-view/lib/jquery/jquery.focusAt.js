jQuery.fn.focusAt = ( function() {
	'use strict';

	/**
	 * Calculates the position within a string relative to a string's start (0). Can take a position
	 * relative to the strings start (positive number) or relative to the strings end (negative
	 * number).
	 *
	 * @ignore
	 *
	 * @param {number} relativePosition
	 * @param {number} totalLength
	 * @return {number}
	 */
	function calculateAbsolutePosition( relativePosition, totalLength ) {
		if ( relativePosition < 0 ) {
			relativePosition = totalLength + relativePosition;
			return relativePosition < 0 ? 0 : relativePosition;
		} else {
			return relativePosition > totalLength ? totalLength : relativePosition;
		}
	}

	/**
	 * Helper which will normalize a given position or throw an error if it is an invalid one.
	 *
	 * @ignore
	 *
	 * @param {number|string} position Either a number specifying the position or one of the strings
	 *        "start" and "end".
	 * @param {jQuery} $forElem
	 * @return {number}
	 *
	 * @throws {Error} if position is not specified properly.
	 */
	function normalizePosition( position, $forElem ) {
		var textLength = $forElem.val().length;

		if ( typeof position === 'number' ) {
			return calculateAbsolutePosition( position, textLength );
		} else if ( position === 'end' ) {
			return textLength; // behind last character
		} else if ( position === 'start' ) {
			return 0;
		}
		throw new Error( 'Focus Position has to be a number or string "start" or "end"' );
	}

	/**
	 * Will set the caret to a given position within an input box.
	 * (see http://stackoverflow.com/questions/512528/set-cursor-position-in-html-textbox)
	 *
	 * @ignore
	 *
	 * @param {HTMLElement} elem
	 * @param {number} caretPos
	 */
	function setCaretPosition( elem, caretPos ) {
		var range;

		if ( elem.createTextRange ) {
			range = elem.createTextRange();
			range.move( 'character', caretPos );
			range.select();
		} else {
			elem.focus();
			if ( elem.selectionStart !== undefined ) {
				elem.setSelectionRange( caretPos, caretPos );
			}
		}
	}

	/**
	 * `jQuery.focusAt` introduces a `focusAt` function to jQuery instances. This allows to focus an
	 * element and set the caret to a certain position within the input element.
	 *
	 *     @example
	 *     $( 'input' ).val( 'Foo Bar' ).focusAt( 0 );     // |Foo Bar
	 *     $( 'input' ).val( 'Foo Bar' ).focusAt( 2 );     // Fo|o Bar
	 *     $( 'input' ).val( 'Foo Bar' ).focusAt( -1 );    // Foo Ba|r
	 *     $( 'input' ).val( 'Foo Bar' ).focusAt( 'end' ); // Foo Bar|
	 *     $( 'input' ).val( 'Foo Bar' ).focusAt( 999 );   // Foo Bar|
	 *     $( 'input' ).val( 'Foo Bar' ).focusAt( -999 );  // |Foo Bar
	 *
	 * @member jQuery.fn
	 * @method focusAt
	 * @license GNU GPL v2+
	 * @author Daniel Werner
	 *
	 * @param {number|string} position Either a number specifying the position or one of the strings
	 *        "start" and "end".
	 */
	var focusAt = function focusAt( position ) {
		// If we have a collection of elements, only consider the first one, just like the native
		// jQuery.fn.focus does.
		var $elem = this.eq( 0 );

		// We normalize the position in any case, so if an invalid position is given even for a
		// non-input element, there will still an error be thrown for consistence.
		var normalizedPosition = normalizePosition( position, $elem );

		if ( $elem.length ) {
			if ( !$elem.is( ':input' ) ) {
				$elem.focus(); // simple focus suffices since this is not an input element
			} else {
				setCaretPosition( $elem[0], normalizedPosition );
			}
		}
		return this;
	};

	return focusAt;

}() );
